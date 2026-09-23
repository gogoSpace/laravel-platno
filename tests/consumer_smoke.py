#!/usr/bin/env python3
"""Create a fresh, disposable Laravel consumer and verify real HTTP publication.

Development only: Python's standard library, Composer and PHP. Never accepts an
existing application path or database. All writes stay under this repo's .sandbox.
"""

import hashlib
import http.cookiejar
import json
import os
from pathlib import Path
import re
import secrets
import socket
import sqlite3
import subprocess
import sys
import tempfile
import time
import urllib.error
import urllib.parse
import urllib.request
import uuid


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, request, response, code, message, headers, address):
        return None


def require(condition, message):
    if not condition:
        raise AssertionError(message)


def request_token(document):
    match = re.search(r'name="_token" value="([^"]+)"', document)
    require(match is not None, "A real CSRF token must be present")
    return match.group(1)


def revision(document):
    match = re.search(r'name="revision" value="(\d+)"', document)
    require(match is not None, "A revision token must be present")
    return int(match.group(1))


def run():
    major = sys.argv[1] if len(sys.argv) == 2 else "13"
    require(major in {"12", "13"}, "Choose Laravel 12 or 13")
    repository = Path(__file__).resolve().parents[1]
    sandbox = repository / ".sandbox"
    sandbox.mkdir(exist_ok=True)
    application = Path(tempfile.mkdtemp(prefix=f"consumer{major}-", dir=sandbox))
    database = application / "database/database.sqlite"
    environment = {
        name: value
        for name, value in os.environ.items()
        if not name.startswith(("APP_", "DB_", "DATABASE_", "REDIS_", "CACHE_", "QUEUE_", "SESSION_", "MAIL_", "AWS_", "PLATNO_", "LOG_"))
    }
    environment.update({
        "APP_ENV": "local", "APP_DEBUG": "false", "DB_CONNECTION": "sqlite",
        "DB_DATABASE": str(database), "CACHE_STORE": "database",
        "SESSION_DRIVER": "database", "QUEUE_CONNECTION": "sync", "MAIL_MAILER": "log",
    })
    log_path = sandbox / (application.name + ".log")

    def command(arguments, *, secret_output=False, expected=0, input_text=None):
        completed = subprocess.run(arguments, cwd=application, env=environment, text=True,
                                   stdout=subprocess.PIPE, stderr=subprocess.STDOUT, timeout=300, input=input_text)
        if not secret_output:
            with log_path.open("a") as log:
                log.write("\n" + " ".join(arguments) + "\n" + completed.stdout)
        require(completed.returncode == expected, f"Command failed: {arguments}; inspect {log_path}")
        return completed.stdout

    print(f"Creating isolated Laravel {major} application: {application}", flush=True)
    command(["composer", "create-project", "laravel/laravel", str(application), f"^{major}.0",
             "--no-dev", "--no-interaction", "--prefer-dist", "--no-progress"])
    require(database.is_file() and database.resolve().is_relative_to(application), "Database isolation failed")
    # Persist the same local configuration so this disposable app can be inspected later.
    environment_file = application / ".env"
    contents = environment_file.read_text()
    for name in ("APP_ENV", "APP_DEBUG", "DB_CONNECTION", "DB_DATABASE", "CACHE_STORE", "SESSION_DRIVER", "QUEUE_CONNECTION", "MAIL_MAILER"):
        contents = re.sub(rf"^#?\s*{name}=.*$", f"{name}={environment[name]}", contents, flags=re.MULTILINE)
        if not re.search(rf"^{name}=", contents, re.MULTILINE):
            contents += f"\n{name}={environment[name]}\n"
    environment_file.write_text(contents)
    environment_file.chmod(0o600)
    command(["composer", "config", "repositories.platno", "path", str(repository)])
    command(["composer", "require", "platno/laravel:@dev", "--update-no-dev", "--no-interaction", "--no-progress"])
    installed = json.loads((application / "vendor/composer/installed.json").read_text())["packages"]
    installed_names = {package["name"] for package in installed}
    require(not {"orchestra/testbench", "phpunit/phpunit", "laravel/pint"} & installed_names, "Consumer installed development tools")
    routes_before = json.loads(command(["php", "artisan", "route:list", "--json"]))
    require(not any((route["name"] or "").startswith("platno.") for route in routes_before), "Provider mounted routes implicitly")
    connection = sqlite3.connect(database)
    connection.execute("CREATE TABLE host_proof (value TEXT NOT NULL)")
    connection.execute("INSERT INTO host_proof VALUES ('preserved')")
    connection.commit()
    host_migration = application / "database/migrations/2099_01_01_000000_host_pending.php"
    host_migration.write_text("<?php throw new RuntimeException('Do not run pending host migrations');\n")
    command(["php", "artisan", "platno:install", "--no-interaction"])
    require(connection.execute("SELECT COUNT(*) FROM users").fetchone()[0] == 0, "Installer created a host user")
    host_routes = application / "routes/web.php"
    original_routes = host_routes.read_text()
    middleware = application / "app/Http/Middleware/AllowContentEditing.php"
    middleware.parent.mkdir(parents=True, exist_ok=True)
    middleware.write_text("""<?php
namespace App\\Http\\Middleware;
final class AllowContentEditing {
    public function handle($request, $next) {
        abort_unless($request->header('X-Acceptance-Editor') === 'allowed', 403);
        return $next($request);
    }
}
""")
    mounted_routes = original_routes + "\nRoute::get('/host-proof', fn () => response('Host preserved'.csrf_field()));\nRoute::middleware(\\App\\Http\\Middleware\\AllowContentEditing::class)->group(fn () => \\Platno\\Platno::editorRoutes());\n\\Platno\\Platno::publicRoutes();\n"
    host_routes.write_text(mounted_routes)
    command(["php", "artisan", "platno:make-plugin", "Callout", "--type=example.callout"])
    plugin_files = [application / "app/Platno/Callout.php", application / "resources/views/platno-custom/callout.blade.php"]
    plugin_contents = [path.read_bytes() for path in plugin_files]
    command(["php", "artisan", "platno:make-plugin", "Callout", "--type=example.callout"], expected=1)
    require([path.read_bytes() for path in plugin_files] == plugin_contents, "Generator overwrote host files")
    configuration = application / "config/platno.php"
    configuration.write_text(configuration.read_text().replace("        Text::class,", "        Text::class,\n        \\App\\Platno\\Callout::class,"))
    override = application / "resources/views/vendor/platno/plugins/text.blade.php"
    override.parent.mkdir(parents=True, exist_ok=True)
    override.write_text('<p>Host override: {{ $data["text"] }}</p>')
    command(["php", "artisan", "route:cache"])
    command(["php", "artisan", "config:cache"])
    command(["php", "artisan", "view:cache"])
    # A late exact collision must also fail; this catches route-collection replacement.
    command(["php", "artisan", "route:clear"])
    host_routes.write_text(mounted_routes + "\nRoute::get('/pages/{slug}', fn () => response('Collision'));\n")
    command(["php", "artisan", "route:list", "--json"], expected=1)
    host_routes.write_text(mounted_routes)
    command(["php", "artisan", "route:cache"])
    configuration_hash = hashlib.sha256((application / "config/platno.php").read_bytes()).hexdigest()
    environment_hash = hashlib.sha256(environment_file.read_bytes()).hexdigest()

    with socket.socket() as listener:
        listener.bind(("127.0.0.1", 0))
        port = listener.getsockname()[1]
    base_address = f"http://127.0.0.1:{port}"
    server_log = (application / "storage/logs/smoke-server.log").open("w")
    server = subprocess.Popen(["php", "-d", "upload_max_filesize=12M", "-d", "post_max_size=14M", "-S", f"127.0.0.1:{port}", "-t", "public", "public/index.php"],
                              cwd=application, env=environment, stdout=server_log, stderr=server_log)
    cookies = http.cookiejar.CookieJar()
    editor = urllib.request.build_opener(NoRedirect(), urllib.request.HTTPCookieProcessor(cookies))
    anonymous = urllib.request.build_opener(NoRedirect())

    editor_allowed = False

    def request(path, *, fields=None, client=editor, expected=200, binary=False, accept_json=False):
        data = urllib.parse.urlencode(fields).encode() if fields is not None else None
        operation = urllib.request.Request(base_address + path, data=data, headers={"Accept": "application/json" if accept_json else "text/html", **({"X-Acceptance-Editor": "allowed"} if client is editor and editor_allowed else {})})
        try:
            response = client.open(operation, timeout=15)
        except urllib.error.HTTPError as exception:
            response = exception
        contents = response.read()
        body = contents if binary else contents.decode()
        require(response.status == expected, f"{path}: expected {expected}, got {response.status}; inspect {application}/storage/logs")
        return body, response.headers

    def upload(name, contents, mime_type, token, *, expected=201):
        boundary = "platno-" + secrets.token_hex(16)
        body = (f"--{boundary}\r\nContent-Disposition: form-data; name=\"_token\"\r\n\r\n{token}\r\n"
                f"--{boundary}\r\nContent-Disposition: form-data; name=\"file\"; filename=\"{name}\"\r\n"
                f"Content-Type: {mime_type}\r\n\r\n").encode() + contents + f"\r\n--{boundary}--\r\n".encode()
        operation = urllib.request.Request(base_address + "/platno/media", data=body, headers={
            "Accept": "application/json", "Content-Type": "multipart/form-data; boundary=" + boundary,
            **({"X-Acceptance-Editor": "allowed"} if editor_allowed else {}),
        })
        try:
            response = editor.open(operation, timeout=20)
        except urllib.error.HTTPError as exception:
            response = exception
        require(response.status == expected, f"Upload expected {expected}, got {response.status}")
        return json.loads(response.read()) if expected != 419 else None

    def block(kind, data):
        return {"id": str(uuid.uuid4()), "type": kind, "version": 1, "data": data}

    try:
        for attempt in range(100):
            try:
                with socket.create_connection(("127.0.0.1", port), timeout=0.1):
                    break
            except OSError:
                require(server.poll() is None, "HTTP server stopped")
                time.sleep(0.05)
        host_form, _ = request("/host-proof")
        request("/platno", client=anonymous, expected=403)
        request("/platno/pages", fields={"_token": request_token(host_form), "title": "Intruder", "slug": "intruder"}, expected=403)
        request("/platno/pages/1", fields={"_token": request_token(host_form), "_method": "PUT", "revision": 1}, expected=403)
        request("/platno/pages/1/publish", fields={"_token": request_token(host_form), "revision": 1}, expected=403)
        editor_allowed = True
        request("/platno/login", expected=404)
        request("/platno/logout", fields={"_token": request_token(host_form)}, expected=404)
        require(connection.execute("SELECT COUNT(*) FROM sqlite_master WHERE name IN ('platno_administrators','platno_access_keys')").fetchone()[0] == 0, "Package created authentication tables")
        form, _ = request("/platno/pages/create")
        request("/platno/pages", fields={"title": "Without token", "slug": "without-token"}, expected=419)
        _, created = request("/platno/pages", fields={"_token": request_token(form), "title": "First page", "slug": "first-page", "text": "Original draft"}, expected=302)
        edit_path = urllib.parse.urlparse(created["Location"]).path
        page_identifier = re.search(r"/pages/(\d+)/edit", edit_path).group(1)
        save_path = f"/platno/pages/{page_identifier}"
        publish_path = save_path + "/publish"
        request("/pages/first-page", client=anonymous, expected=404)
        form, _ = request(edit_path)
        request(save_path, fields={"_method": "PUT", "revision": revision(form), "title": "Without token", "text": "Denied"}, expected=419)
        request(save_path, fields={"_token": request_token(form), "_method": "PUT", "revision": revision(form), "title": "<b>Published title</b>", "text": "<script>unsafe()</script>\nPublished text"}, expected=302)
        form, _ = request(edit_path)
        request(publish_path, fields={"revision": revision(form)}, expected=419)
        request(publish_path, fields={"_token": request_token(form), "revision": revision(form)}, expected=302)
        public, headers = request("/pages/first-page", client=anonymous)
        require("&lt;script&gt;" in public and "<script>" not in public and "&lt;b&gt;Published title&lt;/b&gt;" in public, "Publication output must be escaped")
        require("no-store" in headers.get("Cache-Control", ""), "Public cache policy missing")
        first_publication = connection.execute("SELECT * FROM platno_publications").fetchall()
        form, _ = request(edit_path)
        stale_revision = revision(form)
        request(save_path, fields={"_token": request_token(form), "_method": "PUT", "revision": stale_revision, "title": "Next title", "text": "Still private"}, expected=302)
        public, _ = request("/pages/first-page", client=anonymous)
        require("Still private" not in public, "Draft leaked into publication")
        conflict, _ = request(save_path, fields={"_token": request_token(form), "_method": "PUT", "revision": stale_revision, "title": "Unsaved work", "text": "<script>Recover this text</script>"}, expected=409)
        require("&lt;script&gt;Recover this text&lt;/script&gt;" in conflict, "Conflict lost the submitted text")
        request(publish_path, fields={"_token": request_token(form), "revision": stale_revision}, expected=409)
        require(connection.execute("SELECT * FROM platno_publications").fetchall() == first_publication, "Old snapshot was mutated")
        rows_before = {table: connection.execute(f"SELECT * FROM {table}").fetchall() for table in ("platno_pages", "platno_publications", "platno_assets", "platno_asset_references", "host_proof")}
        command(["php", "artisan", "platno:install", "--no-interaction"])
        for table, rows in rows_before.items():
            require(connection.execute(f"SELECT * FROM {table}").fetchall() == rows, f"Reinstallation changed {table}")
        require(hashlib.sha256((application / "config/platno.php").read_bytes()).hexdigest() == configuration_hash, "Configuration overwritten")
        require(hashlib.sha256(environment_file.read_bytes()).hexdigest() == environment_hash, "Environment overwritten")
        require(host_routes.read_text() == mounted_routes, "Host routes overwritten")
        form, _ = request(edit_path)
        request(publish_path, fields={"_token": request_token(form), "revision": revision(form)}, expected=302)
        public, _ = request("/pages/first-page", client=anonymous)
        require("Still private" in public, "Second publication not visible")
        # Full product journey: custom plugin, nested content, native media and lifecycle.
        form, _ = request(edit_path)
        token = request_token(form)
        image_contents = (repository / "examples/assets/notebook.png").read_bytes()
        upload("notebook.png", image_contents, "image/png", "", expected=419)
        upload("unsafe.svg", b'<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>', "image/svg+xml", token, expected=422)
        asset = upload("notebook.png", image_contents, "image/png", token)
        media_path = "/pages/media/" + asset["id"]
        request(media_path, client=anonymous, expected=404)
        document = {"version": 1, "blocks": [
            block("heading", {"text": "From blank to published", "level": "h2"}),
            block("text", {"text": "A complete page with portable blocks."}),
            block("rich-text", {"content": [{"type": "paragraph", "runs": [{"text": "Structured emphasis", "bold": True, "italic": False, "link": ""}]}]}),
            block("link", {"label": "Explore Laravel", "url": "https://laravel.com", "style": "button"}),
            block("group", {"tone": "muted", "children": [block("image", {"asset": asset["id"], "alt": "An open notebook", "caption": "Original uploaded image"})]}),
            block("example.callout", {"text": "Generated custom plugin works"}),
        ]}
        # Canvas uses real plugin views without saving or exposing a draft image.
        before_canvas = {table: connection.execute(f"SELECT * FROM {table}").fetchall() for table in ("platno_pages", "platno_publications", "platno_asset_references")}
        request("/platno/canvas", fields={"document": json.dumps(document)}, expected=419)
        request("/platno/canvas", fields={"_token": token, "document": json.dumps(document)}, client=anonymous, expected=419)
        canvas, _ = request("/platno/canvas", fields={"_token": token, "document": json.dumps(document)}, accept_json=True)
        canvas = json.loads(canvas)
        require("Generated custom plugin works" in canvas["html"] and "Host override:" in canvas["html"], "Canvas did not use host plugin views")
        require("document.blocks.4.data.children.0" in canvas["html"], "Nested canvas identity missing")
        require("/platno/media/" + asset["id"] in canvas["html"], "Canvas must use private media")
        for table, rows in before_canvas.items():
            require(connection.execute(f"SELECT * FROM {table}").fetchall() == rows, f"Canvas changed {table}")
        request(media_path, client=anonymous, expected=404)
        for module in ("workspace", "editor-store", "editor-canvas"):
            request(f"/platno/assets/{module}.js")
        _, created = request("/platno/pages", fields={"_token": token, "title": "Complete page", "slug": "complete-page", "document": json.dumps(document)}, expected=302)
        complete_edit_path = urllib.parse.urlparse(created["Location"]).path
        complete_identifier = re.search(r"/pages/(\d+)/edit", complete_edit_path).group(1)
        complete_save_path = f"/platno/pages/{complete_identifier}"
        form, _ = request(complete_edit_path)
        require(connection.execute("SELECT document FROM platno_pages WHERE id=?", (complete_identifier,)).fetchone() is not None, "Composition not saved")
        stored = json.loads(connection.execute("SELECT document FROM platno_pages WHERE id=?", (complete_identifier,)).fetchone()[0])
        require(stored == document, "Multi-block round trip changed plugin data")
        request(complete_save_path + "/preview", client=anonymous, expected=403)
        preview, _ = request(complete_save_path + "/preview")
        require("Generated custom plugin works" in preview and "Host override:" in preview, "Preview/custom renderer missing")
        request("/pages/complete-page", client=anonymous, expected=404)
        request("/platno/media/" + asset["id"], fields={"_token": token, "_method": "DELETE"}, accept_json=True, expected=422)
        request(complete_save_path + "/publish", fields={"_token": token, "revision": revision(form)}, expected=302)
        published, _ = request("/pages/complete-page", client=anonymous)
        require("Generated custom plugin works" in published and 'href="https://laravel.com"' in published and 'alt="An open notebook"' in published, "Complete publication is incomplete")
        public_image, _ = request(media_path, client=anonymous, binary=True)
        require(public_image == image_contents, "Published original bytes changed")
        snapshot = connection.execute("SELECT id, document FROM platno_publications WHERE page_id=?", (complete_identifier,)).fetchone()
        form, _ = request(complete_edit_path)
        changed = json.loads(json.dumps(document))
        changed["blocks"][1]["data"]["text"] = "A later private draft"
        request(complete_save_path, fields={"_token": token, "_method": "PUT", "revision": revision(form), "title": "Complete page", "document": json.dumps(changed)}, expected=302)
        public, _ = request("/pages/complete-page", client=anonymous)
        require("A later private draft" not in public, "Complete document draft leaked")
        form, _ = request(complete_edit_path)
        request(complete_save_path + f"/history/{snapshot[0]}/restore", fields={"_token": token, "revision": revision(form)}, expected=302)
        require(json.loads(connection.execute("SELECT document FROM platno_pages WHERE id=?", (complete_identifier,)).fetchone()[0]) == document, "History restore lost data")
        form, _ = request(complete_edit_path)
        request(complete_save_path + "/unpublish", fields={"_token": token, "revision": revision(form)}, expected=302)
        request("/pages/complete-page", client=anonymous, expected=404)
        request(media_path, client=anonymous, expected=404)
        request("/platno/media/" + asset["id"], fields={"_token": token, "_method": "DELETE"}, accept_json=True, expected=422)
        form, _ = request(complete_edit_path)
        request(complete_save_path + "/archive", fields={"_token": token, "revision": revision(form)}, expected=302)
        form, _ = request(complete_edit_path)
        request(complete_save_path + "/unarchive", fields={"_token": token, "revision": revision(form)}, expected=302)
        form, _ = request(complete_edit_path)
        request(complete_save_path + "/publish", fields={"_token": token, "revision": revision(form)}, expected=302)
        request("/pages/complete-page", client=anonymous)
        require(connection.execute("SELECT document FROM platno_publications WHERE id=?", (snapshot[0],)).fetchone()[0] == snapshot[1], "History snapshot mutated")
        editor_allowed = False
        request(edit_path, expected=403)
        request("/pages/first-page", client=anonymous)
        installed_framework = next(package["version"] for package in installed if package["name"] == "laravel/framework")
        evidence = {"result": "passed", "php": command(["php", "-r", "echo PHP_VERSION;"]).strip(), "laravel": installed_framework, "application": str(application), "database": "SQLite", "sessions_and_cache": "database", "route_cache": True, "config_cache": True, "csrf": True, "consumer_dev_dependencies": False, "host_middleware": True, "composition_media_custom_plugin": True, "non_persisting_canvas": True, "history_and_lifecycle": True}
        (application / "smoke-result.json").write_text(json.dumps(evidence, indent=2) + "\n")
        print(json.dumps(evidence, indent=2), flush=True)
    finally:
        server.terminate()
        server.wait(timeout=10)
        server_log.close()
        connection.close()


if __name__ == "__main__":
    run()
