# Distancia entre CEPs

Necessário ter o docker e php instalado!

Clone o repositório. crie um arquivo .env na pasta raiz do projeto, como o banco de dados é local no Docker, pode manter as informações do Banco de dados. Sendo necessário alterar apenas o token do Cep Aberto API
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

Abra o docker e o mantenha com janela aberta


Na pasta raiz onde se encontra o repositório do projeto, abra um terminal e execute os comandos:
```cmd
docker compose up -d --build
docker compose run --rm distance_cep composer install
docker compose run --rm distance_cep vendor/bin/phinx migrate -e test
```

No docker, acesse o terminal de 'distance_cep_apache' e execute:
```cmd
docker-php-ext-install mysqli
```

Reinicie o container 'distance_cep_apache'

Tentei fazer funcionar pelo DockerFile ou pelo terminal do meu computador mas não tive sucesso.
