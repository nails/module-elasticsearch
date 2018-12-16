class Stats {

    constructor() {
        if ($('.group-elasticsearch.stats').length) {
            this.loadStats();
            $('.js-action-reload-stats')
                .on('click', () => {
                    this.loadStats();
                });
        }
    }

    // --------------------------------------------------------------------------

    loadStats() {

        $('#js-stats-loading').removeClass('hidden');
        $('#js-stats-loaded').addClass('hidden');

        $.get(window.SITE_URL + 'api/elasticsearch/stats')
            .done((data) => {
                $('#js-stats-loading').addClass('hidden');
                $('#js-stats-loaded').removeClass('hidden');
                this.processStats(data.data);
            })
            .fail((response) => {
                let data;
                try {
                    data = JSON.parse(response.responseText);
                } catch (e) {
                    data = {
                        'status': 500,
                        'error': 'An unknown error occurred.'
                    };
                }

                $('#stats-loading')
                    .attr('class', 'alert alert-danger')
                    .html(data.error);
            });
    }

    // --------------------------------------------------------------------------

    processStats(statData) {

        //  Cluster health
        let alertClass = 'warning';
        let alertIcon = 'warning';
        let alertMsg = 'The health of the cluster is unknown.';

        switch (statData.status) {
            case 'green':
                alertClass = 'success';
                alertIcon = 'check';
                alertMsg = '[' + statData.cluster_name + '] Cluster is fully functional.';
                break;

            case 'yellow':
                alertClass = 'warning';
                alertIcon = 'exclamation-triangle';
                alertMsg = '[' + statData.cluster_name + '] Cluster is fully functional but some data has not yet been replicated.';
                break;

            case 'red':
                alertClass = 'warning';
                alertIcon = 'exclamation-triangle';
                alertMsg = '[' + statData.cluster_name + '] Cluster is missing data.';
                break;
        }

        $('#js-cluster-health').attr('class', 'alert alert-' + alertClass);
        $('#js-cluster-health .fa').attr('class', 'fa fa-lg fa-' + alertIcon);
        $('#js-cluster-health .msg').html(alertMsg);

        // --------------------------------------------------------------------------

        //  Indices
        $('.js-stat-indices-count').html(statData.indices.count);
        $('.js-stat-indices-docs-count').html(statData.indices.docs.count);
        $('.js-stat-indices-segments-count').html(statData.indices.segments.count);

        //  Nodes
        //  Counts
        for (let key in statData.nodes.count) {
            if (statData.nodes.count.hasOwnProperty(key)) {
                $('.js-stat-nodes-count-' + key).html(statData.nodes.count[key]);
            }
        }
    }
}

export default Stats;
