import AllTexts from './components/text/AllTexts.vue';
import EditText from './components/text/EditText.vue';
import PageNotFound from './components/PageNotFound.vue';
 
export const routes = [
    {
        name: 'home',
        path: '/',
        component: AllTexts
    },
    {
        name: 'texts',
        path: '/texts/',
        component: AllTexts
    },
    {
        name: 'edit',
        path: '/text/edit/:id',
        component: EditText
    },
    { path: "*", component: PageNotFound }
];