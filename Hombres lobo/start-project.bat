@echo off
cd /d %~dp0

echo Levantando contenedores...
docker compose up -d

echo Migrando BD...
docker compose exec backend php artisan migrate

echo Optimizando Laravel...
docker compose exec backend php artisan optimize

echo Iniciando Vite...
docker compose exec backend npm run dev

pause