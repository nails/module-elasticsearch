/* globals */
/* exports _ELASTICSEARCH_STATS */
var _ELASTICSEARCH_STATS = function() {

    var base = this;

    // --------------------------------------------------------------------------

    base.__construct = function() {
        base.loadStats()
        $('.js-action-reload-stats').on('click', function() {
            base.loadStats();
        });
    };

    // --------------------------------------------------------------------------

    base.loadStats = function() {

        $('#js-stats-loading').removeClass('hidden');
        $('#js-stats-loaded').addClass('hidden');

        $.get(window.SITE_URL + 'api/elasticsearch/stats')
        .done(function(data) {
            $('#js-stats-loading').addClass('hidden');
            $('#js-stats-loaded').removeClass('hidden');
            base.processStats(data.data);
        })
        .fail(function(data) {

            var _data;

            try {

                _data = JSON.parse(data.responseText);

            } catch (e) {

                _data = {
                    'status': 500,
                    'error': 'An unknown error occurred.'
                };
            }

            $('#stats-loading')
                .attr('class', 'alert alert-danger')
                .html(_data.error);
        });
    };

    // --------------------------------------------------------------------------

    base.processStats = function(statData) {

        //  Cluster health
        var  alertClass = 'warning';
        var  alertIcon  = 'warning';
        var  alertMsg   = 'The health of the cluster is unknown.';

        switch (statData.status) {
            case 'green':
                alertClass = 'success';
                alertIcon  = 'check';
                alertMsg   = '[' + statData.cluster_name + '] Cluster is fully functional.';
                break;

            case 'yellow':
                alertClass = 'warning';
                alertIcon  = 'exclamation-triangle';
                alertMsg   = '[' + statData.cluster_name + '] Cluster is fully functional but some data has not yet been replicated.';
                break;

            case 'red':
                alertClass = 'warning';
                alertIcon  = 'exclamation-triangle';
                alertMsg   = '[' + statData.cluster_name + '] Cluster is missing data.';
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
        for (var key in statData.nodes.count) {
            if (statData.nodes.count.hasOwnProperty(key)) {
                $('.js-stat-nodes-count-' + key).html(statData.nodes.count[key]);
            }
        }
    };

    // --------------------------------------------------------------------------

    return base.__construct();
}();