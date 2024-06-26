new Vue({
    el: '#app',
    data: {
        cepModal1: '',
        cepModal2: '',
        cepTable1: '',
        cepTable2: '',
        registros: [],
        logs: [],
        carregando: true,
        importando: false,
        showPopup: false,
        cadastrando: false,
        popupMessage: '',
        typePopup: 'bg-success',
        msImportando: 'importar',
        msCadastrando: 'Calcular e cadastrar'
    },
    mounted() {
        const cacheRegistros = localStorage.getItem('registros');
        if (cacheRegistros) {
            this.registros = JSON.parse(cacheRegistros);
            this.carregando = false;
        } else {
            this.fetchRegistros()
        }

        const cacheLogs = localStorage.getItem('logs');
        if (cacheLogs) {
            this.logs = JSON.parse(cacheLogs);
        } else {
            this.fetchLogs()
        }

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
                    funcao: 'buscarDistancias',
                    cep1: this.cepTable1,
                    cep2: this.cepTable2
                })
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    localStorage.setItem('registros', JSON.stringify(data))
                    this.registros = data;
                    this.carregando = false;
                })
                .catch(error => {
                    this.carregando = false;
                    this.errorPopUp("Erro ao buscar registros");

                });
        },

        fetchLogs() {
            fetch('http://127.0.0.1:8000/php/index.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    funcao: 'logs',
                })
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    localStorage.setItem('logs', JSON.stringify(data))
                    this.logs = data;
                    this.successPopup("Sucesso ao buscar logs");

                })
                .catch(error => {
                    this.errorPopUp("Erro ao buscar logs");
                });
        },

        includeRegistro() {


            this.msCadastrando = 'Cadastrando...'
            this.cadastrando = true

            let cacheCadastro = localStorage.getItem('cadastro') ? JSON.parse(localStorage.getItem('cadastro')) : [];

            console.log(cacheCadastro)

            const jaExiste = cacheCadastro.some(
                registro => registro.cepModal1 === this.cepModal1 && registro.cepModal2 === this.cepModal2
            );

            if (jaExiste) {
                this.errorPopUp('Cadastro já efetuado anteriormente')
                this.cadastrando = false
                this.msCadastrando = 'Calcular e cadastrar'
                return
            }

            fetch('http://127.0.0.1:8000/php/index.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    funcao: 'salvarDistancia',
                    cep1: this.cepModal1,
                    cep2: this.cepModal2
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        this.errorPopUp(data.error)
                    }
                    else {
                        this.successPopup(data.success)
                        cacheCadastro.push({ cepModal1: this.cepModal1, cepModal2: this.cepModal2 })
                        cacheCadastro.push({ cepModal1: this.cepModal2, cepModal2: this.cepModal1 })
                        localStorage.setItem('cadastro', JSON.stringify(cacheCadastro));
                    }
                    this.cadastrando = false
                    this.msCadastrando = 'Calcular e cadastrar'
                    this.cepTable1 = ''
                    this.cepTable2 = ''
                    this.fetchRegistros()
                })
                .catch(error => {

                    this.errorPopUp(error)
                    this.cadastrando = false
                    this.msCadastrando = 'Calcular e cadastrar'
                });
        },

        importarCeps(evento) {

            const arquivo = evento.target.files[0];
            const formData = new FormData();
            formData.append('arquivo', arquivo);
            formData.append('funcao', 'importarCeps');
            this.importando = true
            this.msImportando = "importando..."

            fetch('http://127.0.0.1:8000/php/index.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        this.errorPopUp(data.error)
                    } else {
                        this.successPopup(data.success)
                    }
                    this.fetchRegistros()
                    this.importando = false
                    this.msImportando = "importar"
                })
                .catch(error => {
                    this.errorPopUp(error)
                    this.importando = false
                    this.msImportando = "importar"
                });

        },

        errorPopUp(message) {
            this.popupMessage = message;
            this.typePopup = 'bg-danger';
            this.showPopup = true;
            setTimeout(() => {
                this.showPopup = false;
            }, 10000);
        },

        successPopup(message) {
            this.popupMessage = message;
            this.typePopup = 'bg-success';
            this.showPopup = true;
            setTimeout(() => {
                this.showPopup = false;
            }, 10000);
        }
    }
});

Vue.component('cep-input', {
    template: `
        <input type="text" class="form-control" :class="{'border-danger': erro}" v-model="cep" @input="onInput" @blur="validarCep" placeholder="00000-000" aria-label="CEP">
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
        },
        onInput(event) {
            this.formatarCep();
            this.$emit('input', this.cep);
        }
    },
    watch: {
        value(newValue) {
            this.cep = newValue;
        }
    }
});