# Distancia entre CEPs

Necessário ter o docker e php instalado!

Clone o repositório, abra o terminal no diretório.

Crie um arquivo .env no diretório principal
```.env
CEP_ABERTO_TOKEN=seu_token_api_cep_aberto
```

Execute os comandos:
```cmd
docker compose up -d --build
docker compose run --rm distance_cep composer install
docker compose run --rm distance_cep vendor/bin/phinx migrate -e test
```
