# Distancia entre CEPs

## Sobre o desafio técnico
Cadastro de distância entre CEPs. Sugerimos a criação de um sistema simples, contendo front e backend, de acordo com os requisitos.<br>
Regras: <br>
projeto deverá atender os seguintes requisitos:
- Persistir em banco de dados:
    - CEP origem
    - CEP destino
    - Distância calculada entre os CEPs
    - Data/hora de cadastro
    - Data/hora de alteração
- Tela de exibição de lista de distâncias já calculadas
- Opção de adicionar nova distância
- Ao digitar o CEP deve se validar que ele existe através da API CEP Aberto
- Após receber as coordenadas da API externa, o sistema deve calcular a distância entre
as coordenadas sem usar API externa
- Opção de cálculo em massa através de importação de arquivo .csv. Validar CEPs e calcular distância, semelhante aos passos anteriores. O arquivo de entrada deve ter os seguintes colunas:
    - CEP origem
    - CEP fim
- Após calcular e exibir a distância calculada, persistir o cálculo
- Cache das consultas da API externa
- Backend deve ser PHP (prioritário) ou Go
- O projeto deve rodar em Docker
### Observações
- Não está previsto nesta avaliação o controle de permissões de acesso
- O uso de framework é opcional, e fica a sua escolha
### Recomendações:
Não é obrigatório, mas seria muito legal se você utilizasse:
- Bootstrap 4
- VueJS 2
- Phinx
- Limitar a quantidade de cálculos para evitar o bloqueio da api externa
- Importação ser consumida por um sistema de filas, como o RabbitMQ
- Tela de logs estruturados

<br>

## Instalação
Necessário ter o docker instalado!

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
docker compose up -d
```




Acesse a aplicação pelo navegador: [localhost:8000](http://localhost:8000/)
