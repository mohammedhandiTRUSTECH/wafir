module.exports = {
  apps: [
    {
      name: "wafir-backend",
      cwd: "/home/server/Desktop/project/wafir/backend",
      script: "php",
      args: "artisan serve --host=127.0.0.1 --port=8010",
      interpreter: "none",
      autorestart: true,
      watch: false,
      out_file: "/home/server/Desktop/project/wafir/logs/backend.out.log",
      error_file: "/home/server/Desktop/project/wafir/logs/backend.err.log",
    },
    {
      name: "wafir-proxy",
      cwd: "/home/server/Desktop/project/wafir",
      script: "/home/server/Desktop/project/wafir/scripts/wafir-proxy.js",
      interpreter: "node",
      autorestart: true,
      watch: false,
      env: {
        WAFIR_PROXY_PORT: "3014",
        WAFIR_BACKEND_PORT: "8010",
        WAFIR_STATIC_ROOT: "/home/server/Desktop/project/wafir/frontend/build",
      },
      out_file: "/home/server/Desktop/project/wafir/logs/proxy.out.log",
      error_file: "/home/server/Desktop/project/wafir/logs/proxy.err.log",
    },
  ],
};
