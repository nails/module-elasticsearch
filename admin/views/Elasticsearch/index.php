<div class="group-elasticsearch stats">
    <div class="alert alert-info" id="js-stats-loading">
        <b class="fa fa-spin fa-spinner"></b>
        Loading stats
    </div>
    <div id="js-stats-loaded" class="hidden">
        <div class="alert alert-info" id="js-cluster-health">
            <p>
                <b class="fa"></b>
                <span class="msg"></span>
                <button class="pull-right btn btn-primary btn-xs js-action-reload-stats">
                    Reload
                </button>
            </p>
        </div>
        <h3>System Statistics</h3>
        <div class="row">
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-body" id="js-stats-indices">
                        <h4>Indices</h4>
                        <table>
                            <thead>
                                <tr>
                                    <th>Counts</th>
                                    <th class="value"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Total</td>
                                    <td class="value js-stat-indices-count"></td>
                                </tr>
                              <tr>
                                    <td>Total Docs</td>
                                    <td class="value js-stat-indices-docs-count"></td>
                                </tr>
                              <tr>
                                    <td>Total Segments</td>
                                    <td class="value js-stat-indices-segments-count"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-body" id="js-stats-nodes">
                        <h4>Nodes</h4>
                        <table>
                            <thead>
                                <tr>
                                    <th>Counts</th>
                                    <th class="value"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Total</td>
                                    <td class="value js-stat-nodes-count-total"></td>
                                </tr>
                                <tr>
                                    <td>Client</td>
                                    <td class="value js-stat-nodes-count-client"></td>
                                </tr>
                                <tr>
                                    <td>Data Only</td>
                                    <td class="value js-stat-nodes-count-data_only"></td>
                                </tr>
                                <tr>
                                    <td>Master Data</td>
                                    <td class="value js-stat-nodes-count-master_data"></td>
                                </tr>
                                <tr>
                                    <td>Master Only</td>
                                    <td class="value js-stat-nodes-count-master_only"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
