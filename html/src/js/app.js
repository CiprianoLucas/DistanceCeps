import { createApp, ref } from 'https://unpkg.com/vue@3/dist/vue.esm-browser.js'


$('.cep-input').on('blur', function() {
    let cep = $(this).val();


    if (cep.length === 0 || cep.length === 9) {
        $(this).removeClass('border border-danger');

    } else {
        $(this).addClass('border border-danger');
    }
});

const app = Vue.createApp({})
.component('navbar', {
template:`
        <nav>
            <a href="">
                <div>
                    <span>Teste</span>
                </div>
            </a>
        </nav>
`

})
.mount('#app')