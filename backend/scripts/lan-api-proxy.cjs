#!/usr/bin/env node
/*
 * Dev-only LAN bridge for the mobile app.
 *
 * Windows Firewall blocks inbound traffic to php.exe, so a phone cannot reach
 * `php artisan serve` directly. Node.js already has an allow rule on every
 * profile and port, so this proxy listens on the public port and forwards to
 * Laravel on loopback.
 *
 *   node scripts/lan-api-proxy.cjs            # 0.0.0.0:8000 -> 127.0.0.1:8001
 *   node scripts/lan-api-proxy.cjs 8000 8001  # explicit listen / target ports
 *
 * Run Laravel on the target port:
 *   php artisan serve --host=127.0.0.1 --port=8001
 */
const http = require('http');
const net = require('net');

const LISTEN_PORT = Number(process.argv[2]) || 8000;
const TARGET_PORT = Number(process.argv[3]) || 8001;
const TARGET_HOST = '127.0.0.1';

const server = http.createServer((req, res) => {
  const proxyReq = http.request(
    {
      host: TARGET_HOST,
      port: TARGET_PORT,
      method: req.method,
      path: req.url,
      headers: { ...req.headers, host: `${TARGET_HOST}:${TARGET_PORT}` },
    },
    (proxyRes) => {
      res.writeHead(proxyRes.statusCode, proxyRes.headers);
      proxyRes.pipe(res);
    }
  );

  proxyReq.on('error', (err) => {
    const unavailable = err.code === 'ECONNREFUSED';
    res.writeHead(unavailable ? 503 : 502, { 'Content-Type': 'application/json' });
    res.end(
      JSON.stringify({
        message: unavailable
          ? `Laravel is not running on ${TARGET_HOST}:${TARGET_PORT}. Start it with: php artisan serve --host=127.0.0.1 --port=${TARGET_PORT}`
          : `LAN proxy could not reach Laravel: ${err.message}`,
      })
    );
  });

  req.pipe(proxyReq);
});

server.on('clientError', (err, socket) => {
  if (socket.destroyed || !socket.writable) return;
  if (err.code === 'ECONNRESET' || !socket.readable) return socket.destroy();
  if (err.message.includes('parse')) socket.end('HTTP/1.1 400 Bad Request\r\n\r\n');
});

// WebSocket upgrade (Expo refresh, Laravel live logs) passes straight through.
server.on('upgrade', (req, socket, head) => {
  const upstream = net.connect(TARGET_PORT, TARGET_HOST, () => {
    const headers = Object.entries(req.headers)
      .map(([k, v]) => `${k}: ${v}`)
      .join('\r\n');
    upstream.write(`${req.method} ${req.url} HTTP/1.1\r\n${headers}\r\n\r\n`);
    if (head && head.length) upstream.write(head);
    socket.pipe(upstream).pipe(socket);
  });

  upstream.on('error', () => socket.destroy());
  socket.on('error', () => upstream.destroy());
});

server.listen(LISTEN_PORT, '0.0.0.0', () => {
  console.log(`LAN API proxy listening on 0.0.0.0:${LISTEN_PORT}, forwarding to ${TARGET_HOST}:${TARGET_PORT}`);
});
