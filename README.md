# Distancia entre CEPs

Necessário ter o docker e php instalado!

Clone o repositório, abra o terminal no diretório.

Execute os comandos:
```
docker compose up -d --build
docker compose run --rm distance_cep composer install
docker compose run --rm distance_cep vendor/bin/phinx migrate -e test
```