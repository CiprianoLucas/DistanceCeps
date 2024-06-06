import { createApp, ref } from 'https://unpkg.com/vue@3/dist/vue.esm-browser.js'
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