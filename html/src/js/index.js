new Vue({
    el: '#app',
    data: {
        products: []
    },
    mounted() {
        // Faz a requisição HTTP para o backend PHP
        fetch('index.php')
            .then(response => response.json())
            .then(data => {
                // Atualiza o array de produtos com os dados recebidos
                this.products = data;
            });
    }
});