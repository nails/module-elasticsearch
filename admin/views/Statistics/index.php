<div class="group-elasticsearch stats" id="js-elasticsearch-stats">
    <div class="alert alert-info" id="js-elasticsearch-stats-loading">
        <b class="fa fa-spin fa-spinner"></b>
        Loading stats
    </div>
    <div id="js-elasticsearch-stats-loaded" class="hidden">
        <div class="alert alert-info" id="js-elasticsearch-stats-cluster-health">
            <p>
                <b class="fa"></b>
                <span class="msg"></span>
                <button class="float-end btn btn-primary btn-xs" id="js-elasticsearch-stats-reload">
                    Reload
                </button>
            </p>
        </div>
        <table class="table table-striped table-hover table-bordered table-responsive">
            <thead class="table-dark">
                <tr>
                    <th>Label</th>
                    <th class="value">Value</th>
                </tr>
            </thead>
            <tbody class="align-middle" id="js-elasticsearch-stats-details">
            </tbody>
        </table>
    </div>
</div>
