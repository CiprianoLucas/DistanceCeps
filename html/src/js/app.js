new Vue({
    el: '#app',
    data: {
        cepModal1: '',
        cepModal2: '',
        cepTable1: '',
        cepTable2: '',
        registros: [],
        logs: [],
        carregando: true
    },
    mounted() {
        this.fetchRegistros()
        this.fetchLogs()
    },
    methods: {
        fetchRegistros() {
            this.carregando = true
            fetch('http://127.0.0.1:8000/php/index.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    cep1: this.cepTable1,
                    cep2: this.cepTable2
                })
            })
                .then(response => response.json())
                .then(data => {
                    registros = JSON.parse(data);
                    this.registros = registros;
                    this.carregando = false;
                })
                .catch(error => {
                    console.error('Erro ao buscar registros:', error);
                    this.carregando = false;
                });
        },

        fetchLogs() {
            fetch('http://127.0.0.1:8000/php/index.php')
                .then(response => response.json())
                .then(data => {
                    logs = JSON.parse(data);
                    this.logs = logs;

                })
                .catch(error => {
                    console.error('Erro ao buscar registros:', error);

                });
        },

        includeRegistro() {
            fetch('http://127.0.0.1:8000/php/index.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    cep1: this.cepModal1,
                    cep2: this.cepModal2
                })
            })
                .then(response => response.json())
                .then(data => {
                })
                .catch(error => {
                });
        }
    }
});

Vue.component('cep-input', {
    template: `
        <input type="text" class="form-control" :class="{'border-danger': erro}" v-model="cep" @input="formatarCep" @blur="validarCep" placeholder="00000-000" aria-label="CEP">
    `,
    props: ['value'],
    data() {
        return {
            cep: this.value,
            erro: false
        };
    },
    methods: {
        formatarCep() {
            let cepFormatado = this.cep.replace(/\D/g, '');
            if (cepFormatado.length > 8) {
                cepFormatado = cepFormatado.slice(0, 8);
            }
            cepFormatado = cepFormatado.replace(/^(\d{5})(\d{1,3})$/, '$1-$2');
            this.cep = cepFormatado;
        },
        validarCep() {
            if (this.cep.length === 0 || this.cep.length === 9) {
                this.erro = false;

            } else {
                this.erro = true;
            }
        }
    },
    watch: {
        value(newValue) {
            this.cep = newValue;
        }
    }
});