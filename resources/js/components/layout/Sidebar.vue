<template>
    <nav id="sidebar" class="shrink">
        <!-- <div class="sidebar-header">
            <div class="header-text">
                <h3>Bootstrap Sidebar</h3>
                <strong>BS</strong>
            </div>
        </div> -->

        <ul class="list-unstyled components">
            <li :class="{'router-link-active': subIsActive('/apikeys')}">
                <a href="#apiKeySubmenu" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                    <i class="fas fa-key"></i>
                    API raktai
                </a>
                <ul class="collapse list-unstyled" id="apiKeySubmenu">
                    <li>
                        <router-link :to="{name: 'texts'}">API raktų sąrašas</router-link>
                    </li>
                </ul>
            </li>
            <li :class="{'router-link-active': subIsActive('/text')}">
                <a href="#homeSubmenu" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                    <i class="fas fa-file-alt"></i>
                    Tekstai
                </a>
                <ul class="collapse list-unstyled" id="homeSubmenu">
                    <li>
                        <router-link :to="{name: 'texts'}">Tekstų sąrašas</router-link>
                    </li>
                    <li>
                        <router-link :to="{name: 'createText'}">Pridėti tekstą</router-link>
                    </li>
                </ul>
            </li>
            <li :class="{'router-link-active': subIsActive('/graphs')}">
                <a href="#">
                    <i class="far fa-chart-bar"></i>
                    Grafikai
                </a>
            </li>
        </ul>

        <!-- <ul class="list-unstyled CTAs">
            <li>
                <a href="https://bootstrapious.com/tutorial/files/sidebar.zip" class="download">Download source</a>
            </li>
        </ul> -->
    </nav>
</template>
<script>
export default {
    methods: {
        subIsActive(input) {
            if(input == "/text" && this.$route.path == "/") {
                return true;
            }
            const paths = Array.isArray(input) ? input : [input];
            return paths.some(path => {
            return this.$route.path.indexOf(path) === 0
            })
        }
    }
}
</script>
