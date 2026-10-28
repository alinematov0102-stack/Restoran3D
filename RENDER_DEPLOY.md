# Render'ga joylash

1. Tashqi MySQL oching (Aiven / TiDB Cloud / Railway).
2. `install/schema.sql`, keyin `install/create_admin.sql` ni import qiling.
3. GitHub'ga push qiling (Dockerfile ildizda).
4. Render -> Web Service -> Runtime: Docker.
5. Environment: DB_HOST, DB_NAME, DB_USER, DB_PASS, APP_ENV=production
6. Admin: /admin/  (admin / admin12345)
