import AllTexts from './components/text/AllTexts.vue';
import AddText from './components/text/AddText.vue';
import ShowText from './components/text/ShowText.vue';
import AllKeys from './components/apiKey/AllKeys.vue';
import TendencyGraph from './components/graph/Tendency.vue';
import Documentation from './components/documentation/Main.vue';

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
    {
        name: 'keys',
        path: '/keys/',
        component: AllKeys
    },
    {
        name: 'graphs',
        path: '/graphs/',
        component: TendencyGraph
    },
    {
        name: 'documentation',
        path: '/documentation/',
        component: Documentation
    },
    { path: "*", component: PageNotFound }
];