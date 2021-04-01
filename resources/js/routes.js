import AllTexts from './components/text/AllTexts.vue';
import AddText from './components/text/AddText.vue';
import ShowText from './components/text/ShowText.vue';

import PageNotFound from './components/PageNotFound.vue';
 
export const routes = [
    {
        name: 'texts',
        path: '/texts/',
        alias: '/',
        component: AllTexts
    },
    {
        name: 'createText',
        path: '/text/create',
        component: AddText
    },
    {
        name: 'showText',
        path: '/text/show',
        component: ShowText
    },
    { path: "*", component: PageNotFound }
];