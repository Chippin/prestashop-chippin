
<div class="panel">

    <div class="panel-heading">
        <img src="/modules/chippin/logo_1.png" alt="Chippin"> Chippin Callback URLs
    </div>

     <div class="form-wrapper">

        <p>Here are your Chippin Callback credentials for this Prestashop. Please insert them in to your <a href="{$chippin_admin_url}" target="_blank">Chippin account</a> under "Callbacks"</p>

        <hr>

        <div class="form-group">
            <label class="control-label col-lg-3 align-right">cancelled:</label>
            {$chippin_base_return_url}cancelled
        </div>

        <div class="form-group">
            <label class="control-label col-lg-3 align-right">completed:</label>
            {$chippin_base_return_url}completed
        </div>

        <div class="form-group">
            <label class="control-label col-lg-3 align-right">paid:</label>
            {$chippin_base_return_url}paid
        </div>

        <div class="form-group">
            <label class="control-label col-lg-3 align-right">contributed:</label>
            {$chippin_base_return_url}contributed
        </div>

        <div class="form-group">
            <label class="control-label col-lg-3 align-right">failed:</label>
            {$chippin_base_return_url}failed
        </div>

        <div class="form-group">
            <label class="control-label col-lg-3 align-right">invited:</label>
            {$chippin_base_return_url}invited
        </div>

        <div class="form-group">
            <label class="control-label col-lg-3 align-right">rejected:</label>
            {$chippin_base_return_url}rejected
        </div>

        <div class="form-group">
            <label class="control-label col-lg-3 align-right">timed_out:</label>
            {$chippin_base_return_url}timed_out
        </div>

    </div>

</div>
