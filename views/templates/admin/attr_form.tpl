<div class="form-group">

    <label class="control-label col-lg-3">
        <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="" data-original-title="Caracteres no válidos: <>;=#{}">
            Meta-Name
        </span>
    </label>

    <div class="col-lg-9">

        <div class="form-group">

            <div class="col-lg-9">
                <input type="text" {if isset($feature_id)}id="meta-name-{$feature_id}"{/if} name="meta-name" class="" value="{$meta_name}">
            </div>

        </div>

    </div>
</div>