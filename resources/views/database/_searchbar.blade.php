        <h1 class="text-center">Chemicals database</h1>
        <br>

        <div class="panel panel-default" style="padding: 20px; background: #D1D1D127;">
            <div class="text-center" style="margin-bottom: 20px;">
                Exact name: use
                <code>%</code> as a wildcard.
                <br>
                <small>
                    <a href="#" onclick="revealExamples()" id="show-examples">Show examples</a>
                </small>
                <div id="examples-wildcard" name="examples-wildcard" style="display:none;">
                    <small>
                        &bull; Starting with 'pyridin':
                        <code>pyridin%</code>
                        <br> &bull; Containing 'bromo':
                        <code>%bromo%</code>
                        <br> &bull; Ending in 'sulfonate':
                        <code>%sulfonate</code>
                    </small>
                </div>
            </div>

            <div class="row">
                <form class="form" action="/database/search#search-results" method="POST" autocomplete="off">
                    {{ csrf_field() }}
                    <div class="col-md-9 col-md-offset-2" style="display:flex; margin-bottom: 17px;">
                        <input style="margin-right: 10px;" type="text" class="form-control input-lg text-center typeahead" id="search" name="search"
                            placeholder='"BnBr" or "benzyl bromide" or "100-39-0" or "remark: homemade"' autofocus>
                        <button type="submit" class="btn btn-lg btn-default">Search</button>
                    </div>

                    <div class="d-none col-md-12 text-center">
                    </div>

                </form>

                <div class="col-md-12 text-center">
                    <a href="/shorthands/create" class="btn btn-link">
                        <strong>&plus;</strong> Add shorthand
                    </a>
                </div>
            </div>

        </div>
    </div>
