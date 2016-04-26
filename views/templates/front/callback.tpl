{*
* Simpleweb
*}

{*l s='Redirect...' mod='chippin'*}
<script type="text/javascript">
    if (window.parent)
        window.parent.document.location.href='{$chippin_order_confirmation_url|addslashes}';
    else
        document.location.href = '{$chippin_order_confirmation_url|addslashes}';
</script>
