const http = require("node:http");
const fs = require("node:fs");
const path = require("node:path");

const LISTEN_HOST = "127.0.0.1";
const LISTEN_PORT = Number(process.env.WAFIR_PROXY_PORT || 3021);
const BACKEND_HOST = process.env.WAFIR_BACKEND_HOST || "127.0.0.1";
const BACKEND_PORT = Number(process.env.WAFIR_BACKEND_PORT || 8020);
const FRONTEND_DIR =
  process.env.WAFIR_FRONTEND_DIR ||
  path.join(__dirname, "..", "frontend", "build");

const MIME_TYPES = {
  ".html": "text/html; charset=utf-8",
  ".js": "application/javascript; charset=utf-8",
  ".css": "text/css; charset=utf-8",
  ".json": "application/json; charset=utf-8",
  ".png": "image/png",
  ".jpg": "image/jpeg",
  ".jpeg": "image/jpeg",
  ".svg": "image/svg+xml",
  ".ico": "image/x-icon",
  ".woff": "font/woff",
  ".woff2": "font/woff2",
  ".map": "application/json",
  ".txt": "text/plain; charset=utf-8",
};

function proxyToBackend(clientReq, clientRes) {
  const proxyReq = http.request(
    {
      host: BACKEND_HOST,
      port: BACKEND_PORT,
      method: clientReq.method,
      path: clientReq.url,
      headers: {
        ...clientReq.headers,
        host: `${BACKEND_HOST}:${BACKEND_PORT}`,
      },
    },
    (proxyRes) => {
      clientRes.writeHead(proxyRes.statusCode || 502, proxyRes.headers);
      proxyRes.pipe(clientRes);
    },
  );

  proxyReq.on("error", (error) => {
    clientRes.writeHead(502, { "content-type": "text/plain; charset=utf-8" });
    clientRes.end(`Bad gateway: ${error.message}`);
  });

  clientReq.pipe(proxyReq);
}

function serveStatic(clientReq, clientRes) {
  const requestPath = decodeURIComponent(
    (clientReq.url || "/").split("?")[0],
  );
  const safePath = path.normalize(requestPath).replace(/^(\.\.[/\\])+/, "");
  let filePath = path.join(FRONTEND_DIR, safePath);

  if (fs.existsSync(filePath) && fs.statSync(filePath).isDirectory()) {
    filePath = path.join(filePath, "index.html");
  }

  if (!fs.existsSync(filePath) || fs.statSync(filePath).isDirectory()) {
    filePath = path.join(FRONTEND_DIR, "index.html");
  }

  if (!filePath.startsWith(FRONTEND_DIR)) {
    clientRes.writeHead(403, { "content-type": "text/plain; charset=utf-8" });
    clientRes.end("Forbidden");
    return;
  }

  const ext = path.extname(filePath).toLowerCase();
  const contentType = MIME_TYPES[ext] || "application/octet-stream";

  fs.readFile(filePath, (error, data) => {
    if (error) {
      clientRes.writeHead(404, { "content-type": "text/plain; charset=utf-8" });
      clientRes.end("Not found");
      return;
    }

    clientRes.writeHead(200, { "content-type": contentType });
    clientRes.end(data);
  });
}

const server = http.createServer((clientReq, clientRes) => {
  const url = clientReq.url || "/";

  if (url.startsWith("/api/")) {
    proxyToBackend(clientReq, clientRes);
    return;
  }

  serveStatic(clientReq, clientRes);
});

server.listen(LISTEN_PORT, LISTEN_HOST, () => {
  console.log(
    `wafir origin proxy listening on http://${LISTEN_HOST}:${LISTEN_PORT} -> backend ${BACKEND_HOST}:${BACKEND_PORT}`,
  );
});
