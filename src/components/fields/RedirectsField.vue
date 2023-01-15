<template>
    <div>
        <header class="k-section-header">
            <h2 class="k-headline">{{ label }}</h2>
        </header>
        <template v-if='currentRedirects.length > 0'>
            <table class="k-redirects">
                <thead>
                    <tr>
                        <th class="column-from">From</th>
                        <th class="column-to">To</th>
                        <th class="column-code">Code</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody v-for="(redirect) in currentRedirects" :key=redirect>
                    <template v-if='(redirect.langCode == currentLang) && (redirect.destId == pageUuid)'>
                        <tr>
                            <td class="column-from"><pre>{{redirect.from}}</pre></td>
                            <td class="column-to"><pre>{{redirect.to}}</pre></td>
                            <td class="column-code"><pre>{{redirect.code}}</pre></td>
                            <td>
                                <button title="Options" type="button" class="k-button" @click="deleteRedirect(redirect.redirectId)">
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
        </template>

        <section v-if='currentRedirects.length == 0'>
            <div class="k-system-info-box">
                <k-empty icon="road-sign">No redirects</k-empty>
            </div>
        </section>
    </div>
</template>

<script>
export default {
    props: {
        label: String,
        multilang: Boolean,
        pageUuid: String,
        currentLang: String,
    },
    data() {
        return {
            redirects: Array
        };
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
        deleteRedirect: function(redirectId) {
            fetch('/kirbyredirects/delete', {
                method: 'DELETE',
                body: JSON.stringify({
                    redirectId: redirectId
                })
            })
                .then(response => {
                    let json = response.json();
                    if (response.status == 200) {
                        return json;
                    } else {
                        return json.then(Promise.reject.bind(Promise));
                    }
                })
                .then(json => {
                    this.redirects = json.redirects;
                });
        }
    },
    computed: {
        currentRedirects: function() {
            var currentRedirects = [];
            if (Array.isArray(this.redirects)) {
                currentRedirects = Object.entries(this.redirects).map((el) => {
                    return el[1];
                });
                function filterByCurrentPage(arr, uuid, langCode) {
                    return arr.filter(el => {
                        return (el.destId == uuid) && (el.langCode == langCode);
                    });
                }
                var currentRedirects = filterByCurrentPage(currentRedirects, this.pageUuid, this.currentLang);
            }

            return currentRedirects;
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
