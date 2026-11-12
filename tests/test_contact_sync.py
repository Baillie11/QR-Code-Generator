"""Integration tests using an isolated SQLite database and local fake CRM."""
import json
import os
from pathlib import Path
import sqlite3
import subprocess
import tempfile
import threading
from http.server import BaseHTTPRequestHandler, HTTPServer

ROOT = Path(__file__).resolve().parents[1]
requests = []
response_status = 503


class Receiver(BaseHTTPRequestHandler):
    def do_POST(self):
        requests.append((self.path, self.headers.get('Authorization'), json.loads(self.rfile.read(int(self.headers['Content-Length'])))))
        self.send_response(response_status)
        self.end_headers()
        if response_status != 204:
            self.wfile.write(json.dumps({'id': 'test-contact'} if response_status == 200 else {'error': 'unavailable'}).encode())

    def log_message(self, *args):
        pass


server = HTTPServer(('127.0.0.1', 0), Receiver)
threading.Thread(target=server.serve_forever, daemon=True).start()
try:
    with tempfile.TemporaryDirectory(prefix='qr-sync-test-') as directory:
        env = dict(os.environ, QR_CONTACTS_DIR=directory, CLICK_MARKETING_URL=f'http://127.0.0.1:{server.server_port}',
                   CLICK_MARKETING_TOKEN='test-only', CLICK_MARKETING_BUSINESS_ID='b3a7f49e-3cb9-42bd-9c30-466d87baa504')

        def run():
            result = subprocess.run(['php', 'sync-contacts.php'], cwd=ROOT, env=env, capture_output=True, text=True)
            return json.loads(result.stdout)

        assert run() == {'status': 'ok', 'synced': 0}
        with sqlite3.connect(Path(directory) / 'contacts.sqlite') as db:
            db.execute('INSERT INTO contacts (request_id,name,business,email,website,created_at) VALUES (?,?,?,?,?,?)',
                       ('a' * 32, 'Test Person', 'Test Business', 'test@example.com', 'https://example.com/', '2026-11-12'))
        db.close()
        assert run() == {'status': 'pending', 'synced': 0}
        response_status = 200
        assert run() == {'status': 'ok', 'synced': 1}
        assert run() == {'status': 'ok', 'synced': 0}
        assert len(requests) == 2
        path, auth, payload = requests[-1]
        assert path == '/api/integrations/qr-code-generator/contacts'
        assert auth == 'Bearer test-only'
        assert payload == dict(businessId=env['CLICK_MARKETING_BUSINESS_ID'], email='test@example.com',
                               contactName='Test Person', businessName='Test Business', website='https://example.com/',
                               source='qr-code-generator', sourceId='qr:' + 'a' * 32)
        with sqlite3.connect(Path(directory) / 'contacts.sqlite') as db:
            db.execute("UPDATE contacts SET request_id = ?", ('b' * 32,))
        db.close()
        response_status = 204
        assert run() == {'status': 'ok', 'synced': 1}
        assert run() == {'status': 'ok', 'synced': 0}
        print('PASS: field mapping, authenticated import, outage queue, retry, acknowledgements and suppressed contacts')
finally:
    server.shutdown()
    server.server_close()
