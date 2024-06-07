# Distancia entre CEPs

Necessário ter o docker e php instalado!

Clone o repositório, abra o terminal no diretório.

Crie um arquivo .env no diretório principal, como o banco de dados é local no Docker, pode manter as informações do Banco de dados. Sendo necessário alterar apenas o token do Cep Aberto API
```.env
# Cep Aberto API
CEP_ABERTO_TOKEN=<seu_token_cep_aberto>
# Banco da dados
DB_SERVERNAME=mysql-container
DB_USERNAME=root
DB_PASSWORD=admin
DB_PORT=3306
DB_NAME=distance_cep
```

Execute os comandos:
```cmd
docker compose up -d --build
docker compose run --rm distance_cep composer install
docker compose run --rm distance_cep vendor/bin/phinx migrate -e test
```
