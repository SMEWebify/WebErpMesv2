<template>
    <nav>
        <ul class="pagination pagniation-sm m-0" >
           <li class="page-item"
               :class="[
                    link.url === null ? 'disabled' : '',
                    link.active ? 'active' : ''
               ]"
               v-for="(link,index) in links"
               :key="index"
            >
            <inertia-link class="page-link" :href="link.url === null ? '#' : link.url"
            v-html="link.label"></inertia-link>
            </li>
        </ul>
    </nav>
</template>

<script>
    export default {
        props: ['links'],
    }
</script>

