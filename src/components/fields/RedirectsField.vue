<template>
    <div>
        <header class="k-section-header">
            <h2 class="k-headline">{{ label }}</h2>
        </header>
        <!-- <template v-if='isKirbyLogPluginLog'> -->
            <table class="k-redirects">
                <thead>
                    <tr>
                        <th class="column-from">From</th>
                        <th class="column-to">To</th>
                        <th class="column-code">Code</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody v-for="(redirect, key) in redirects" :key=redirect>
                    <template v-if='(redirect.langCode == currentLang) && (redirect.destId == pageUuid)'>
                        <tr>
                            <td class="column-from"><pre>{{redirect.from}}</pre></td>
                            <td class="column-to"><pre>{{redirect.to}}</pre></td>
                            <td class="column-code"><pre>{{redirect.code}}</pre></td>
                            <td>
                                <button title="Options" type="button" class="k-button" @click="deleteRedirect(/*$event, redirect,*/ key)">
                                    <span aria-label="Options" role="img" class="k-button-icon k-icon k-icon-remove">
                                        <svg viewBox="0 0 16 16"><use xlink:href="#icon-remove"></use></svg>
                                    </span>
                                    <span class="k-button-text"> Remove </span>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        <!-- </template> -->
    </div>
</template>

<script>
export default {
    props: {
        label: String,
        multilang: Boolean,
        pageUuid: String,
        currentLang: String,
        redirects: Array
    },
    methods: {
        init: function() {
            fetch('/kirbyredirects/all')
                .then(response => response.json())
                .then(json => {
                    this.redirects = json;
                });
            this.pageUuid = this.$store.getters["content/values"]()["uuid"];
            this.currentLang = this.$language.code;
        },
        deleteRedirect: function(/*event, redirect,*/ redirectId) {
            fetch('/kirbyredirects/delete', {
                method: 'DELETE',
                body: JSON.stringify({
                    redirectId: redirectId
                })
            })
                .then(response => response.json())
                .then(json => {
                    this.redirects = json.redirects;
                });
        }
    },
    created() {
        this.init();
    }
}
</script>

<style>
    .k-redirects {
        width: 100%;
        border-spacing: 1px;
    }
    .k-redirects td,
    .k-redirects th {
        text-align: left;
        font-size: var(--text-sm);
        padding: var(--spacing-2);
        white-space: nowrap;
        text-overflow: ellipsis;
        overflow: hidden;
        background: var(--color-white);
    }
</style>
