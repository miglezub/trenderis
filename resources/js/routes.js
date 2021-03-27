import AllTexts from './components/text/AllTexts.vue';
import AddText from './components/text/AddText.vue';
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
        name: 'createText',
        path: '/text/create',
        component: AddText
    },
    { path: "*", component: PageNotFound }
];