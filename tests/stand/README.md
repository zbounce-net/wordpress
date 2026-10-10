# Isolated WordPress review

This stand runs only on `ssh pi`, project `zbounce-wp-review-20261010`, directory `/tmp/zbounce-wp-review-20261010`. It owns its database/site volumes and never uses production credentials. The base compose publishes only loopback. `lan.yaml` optionally exposes port 18990 on pi's private LAN address. Restart both with:

```sh
cd /tmp/zbounce-wp-review-20261010
docker compose -f tests/stand/compose.yaml -f tests/stand/lan.yaml up -d
```

The current browser fixture is page 23; checkout is page 8 and the virtual test product is 17. These IDs are fixtures, not plugin requirements. Use the settings screen for integration toggles and accepted statuses. The test API key `isolated-fixture-key-not-real` selects intercepted API answers: `good@example.org`, `catchall@example.org`, `rejected@example.org`, `disposable@example.org`, `unknown@example.org`, `nomx@example.org`, `failure@example.org`. Other syntactically valid addresses return the fixture's confirmed-mailbox result. Replacing the test key with a purchased real key enables genuine HTTPS API requests using real credits. All outgoing WordPress mail remains suppressed on this stand.

Do not install `mock-api.php` on a live site. It is excluded from the release ZIP. Stand admin passwords are provided separately and are not committed. To destroy only this stand (after review), use `docker compose -f tests/stand/compose.yaml -f tests/stand/lan.yaml down -v`. This removes its own data; never run this against other pi projects.
