const http = require("node:http");
const fs = require("node:fs");
const path = require("node:path");
const { URL } = require("node:url");

const LISTEN_PORT = Number(process.env.WAFIR_PROXY_PORT || 3014);
const BACKEND_PORT = Number(process.env.WAFIR_BACKEND_PORT || 8010);
const STATIC_ROOT =
  process.env.WAFIR_STATIC_ROOT ||
  path.join(__dirname, "..", "frontend", "build");

const MIME = {
  ".html": "text/html; charset=utf-8",
  ".js": "application/javascript; charset=utf-8",
  ".css": "text/css; charset=utf-8",
  ".json": "application/json",
  ".png": "image/png",
  ".jpg": "image/jpeg",
  ".jpeg": "image/jpeg",
  ".gif": "image/gif",
  ".svg": "image/svg+xml",
  ".ico": "image/x-icon",
  ".woff": "font/woff",
  ".woff2": "font/woff2",
  ".map": "application/json",
  ".txt": "text/plain; charset=utf-8",
};

function sendFile(res, filePath, extraHeaders = {}) {
  const ext = path.extname(filePath).toLowerCase();
  res.writeHead(200, {
    "Content-Type": MIME[ext] || "application/octet-stream",
    ...extraHeaders,
  });
  fs.createReadStream(filePath).pipe(res);
}

function proxyApi(clientReq, clientRes) {
  const proxyReq = http.request(
    {
      host: "127.0.0.1",
      port: BACKEND_PORT,
      method: clientReq.method,
      path: clientReq.url,
      headers: {
        ...clientReq.headers,
        host: clientReq.headers.host || "127.0.0.1",
        "x-forwarded-proto": clientReq.headers["x-forwarded-proto"] || "https",
        "x-forwarded-for":
          clientReq.headers["x-forwarded-for"] || clientReq.socket.remoteAddress,
      },
    },
    (proxyRes) => {
      clientRes.writeHead(proxyRes.statusCode || 502, proxyRes.headers);
      proxyRes.pipe(clientRes);
    },
  );
  proxyReq.on("error", (err) => {
    clientRes.writeHead(502, { "content-type": "text/plain; charset=utf-8" });
    clientRes.end(`Bad gateway (backend): ${err.message}`);
  });
  clientReq.pipe(proxyReq);
}

function serveStatic(req, res) {
  try {
    const rawPath = decodeURIComponent(
      new URL(req.url, "http://localhost").pathname,
    );
    let rel = rawPath === "/" ? "/index.html" : rawPath;
    let filePath = path.normalize(path.join(STATIC_ROOT, rel));
    if (!filePath.startsWith(STATIC_ROOT)) {
      res.writeHead(403).end("Forbidden");
      return;
    }
    if (fs.existsSync(filePath) && fs.statSync(filePath).isFile()) {
      const ext = path.extname(filePath).toLowerCase();
      const cacheHeaders =
        rel === "/index.html" || ext === ".html"
          ? { "Cache-Control": "no-cache, no-store, must-revalidate" }
          : {};
      sendFile(res, filePath, cacheHeaders);
      return;
    }
    sendFile(res, path.join(STATIC_ROOT, "index.html"), {
      "Cache-Control": "no-cache, no-store, must-revalidate",
    });
  } catch (err) {
    res.writeHead(500, { "content-type": "text/plain" });
    res.end(String(err.message || err));
  }
}

const server = http.createServer((req, res) => {
  if (req.url.startsWith("/api") || req.url.startsWith("/sanctum")) {
    proxyApi(req, res);
  } else {
    serveStatic(req, res);
  }
});

server.listen(LISTEN_PORT, "0.0.0.0", () => {
  console.log(
    `wafir-proxy on :${LISTEN_PORT} → static ${STATIC_ROOT}, api → :${BACKEND_PORT}`,
  );
});
