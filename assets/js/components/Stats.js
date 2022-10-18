class Stats {

    constructor() {
        if (document.getElementById('js-elasticsearch-stats')) {
            this.loadStats();
            document.getElementById('js-elasticsearch-stats-reload')
                .addEventListener('click', () => {
                    this.loadStats();
                });
        }
    }

    // --------------------------------------------------------------------------

    loadStats() {

        console.log('loading stats');

        let loading = document.getElementById('js-elasticsearch-stats-loading');
        let loaded = document.getElementById('js-elasticsearch-stats-loaded');

        loading.classList.remove('hidden');
        loaded.classList.add('hidden');

        $.get(window.SITE_URL + 'api/elasticsearch/stats')
            .done((data) => {

                loading.classList.add('hidden');
                loaded.classList.remove('hidden');
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

                loading.className = 'alert alert-danger';
                laoding.innerHTML = data.error;
            });
    }

    // --------------------------------------------------------------------------

    processStats(data) {

        console.log(data);
        //  Cluster health
        let alertClass = 'warning';
        let alertIcon = 'warning';
        let alertMsg = 'The health of the cluster is unknown.';

        switch (data.cluster.status) {
            case 'green':
                alertClass = 'success';
                alertIcon = 'check';
                alertMsg = '[' + data.cluster.name + '] Cluster is fully functional.';
                break;

            case 'yellow':
                alertClass = 'warning';
                alertIcon = 'exclamation-triangle';
                alertMsg = '[' + data.cluster.name + '] Cluster is fully functional but some data has not yet been replicated.';
                break;

            case 'red':
                alertClass = 'warning';
                alertIcon = 'exclamation-triangle';
                alertMsg = '[' + data.cluster.name + '] Cluster is missing data.';
                break;
        }

        let clusterHealth = document.getElementById('js-elasticsearch-stats-cluster-health');
        let clusterIcon = clusterHealth.querySelector('.fa');
        let clusterMsg = clusterHealth.querySelector('.msg');

        clusterHealth.className = `alert alert-${alertClass}`;
        clusterIcon.className = `fa fa-lg fa-${alertIcon}`;
        clusterMsg.innerHTML = alertMsg;

        // --------------------------------------------------------------------------

        // Details
        let target = document.getElementById('js-elasticsearch-stats-details');
        target.innerHTML = '';

        for (let key in data.details) {
            if (data.details.hasOwnProperty(key)) {

                let row = document.createElement('tr');
                let cellLabel = document.createElement('td');
                let cellValue = document.createElement('td');

                cellLabel.innerHTML = data.details[key].label;
                cellValue.innerHTML = data.details[key].value;

                row.append(cellLabel)
                row.append(cellValue)

                target.append(row);
            }
        }
    }
}

export default Stats;
