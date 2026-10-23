# CommSy Helm chart

Deploys CommSy 10.6 or later: the application (FrankenPHP, which serves PHP and HTTP in one process) and the
Symfony Messenger workers (`php bin/console messenger:consume --all`). Both use the same image.

The chart does **not** install a database or a search engine. It expects:

| Service | Version | Notes |
|:--- |:--- |:--- |
| MariaDB | 11.8 | `DATABASE_URL` points to it |
| Elasticsearch | 9.x | single node is enough; `commsy.elasticsearchUrl` points to it; the index can be rebuilt from the database |

The image is published to `ghcr.io/commsy/commsy` (tags: `10.6`, `10.6.0`, ...). Earlier releases on Docker Hub
(`effectivewebwork/commsy`) are no longer maintained.

## Install

```bash
helm install commsy oci://ghcr.io/commsy/charts/commsy --version 1.0.0 -f values.yaml
```

Minimal `values.yaml`:

```yaml
commsy:
  defaultUri: https://commsy.example.com
  emailFrom: commsy@example.com
  trustedProxies: 10.0.0.0/8          # proxies in front of the pod (SYMFONY_TRUSTED_PROXIES)
  elasticsearchUrl: http://elastic:9200/
  existingSecrets:
    - commsy-env
persistence:
  files:
    size: 20Gi
ingress:
  enabled: true
  className: nginx
  hosts:
    - host: commsy.example.com
```

The Secrets in `commsy.existingSecrets` provide the environment variables `APP_SECRET`, `DATABASE_URL`
(`mysql://user:password@host:3306/commsy?serverVersion=11.8.9-MariaDB&charset=utf8mb4`) and `MAILER_DSN`.
`JWT_PASSPHRASE` is optional but should be set: it protects the JWT key pair that the first start generates.
`API_READ_SECRET`, `API_WRITE_SECRET` and `API_METRICS_SECRET` enable the API. Other settings of the application
(`LOCALE`, `TIMEZONE`, `ONLINE_OFFICE_BASE_URL`, ...) go into `commsy.extraEnv`.

## Components

* **web**: runs `commsy:update` (schema update) on start, then the web server. The port opens only after the
  update has finished, so the readiness probe also marks the end of the update. Use a generous
  `web.startupProbe.failureThreshold` after a version change.
* **worker**: shares the image and the files volume, skips the update (`COMMSY_SKIP_INIT=1`) and waits until the
  web service has an endpoint.
* **files volume** (`/app/files`): user uploads. A ReadWriteOnce volume is shared by web and worker, therefore
  all pods are scheduled to the same node (`podAffinityForSharedVolume`). The JWT key pair is kept in the
  sub path `jwt` of the same volume.

## Versioning

The chart version follows [SemVer](https://semver.org) and is independent of the CommSy version. `appVersion` is
the CommSy version the chart is written for and the default image tag. Chart releases are tagged `chart-vX.Y.Z`
and published as an OCI artifact to `ghcr.io/commsy/charts/commsy`.

## Upgrading from CommSy 10.5 or earlier

* The 10.6 image runs as `www-data` with uid 33 (the earlier Alpine image used uid 82). A files volume written by
  an older release must be handed over once: `chown -R 33:0 <volume>`.
* Rebuild the Elasticsearch index after moving to Elasticsearch 9.
