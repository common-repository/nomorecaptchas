    function checkLogging()
    {

        if (document.getElementById("checkbox_log").checked === true)
        {

            document.getElementById("checkbox_error").disabled = false;
            document.getElementById("checkbox_parse").disabled = false;
            document.getElementById("checkbox_warn").disabled = false;
            document.getElementById("checkbox_notice").disabled=false;
        }
        else
        {

            document.getElementById("checkbox_error").disabled = true;
            document.getElementById("checkbox_parse").disabled = true;
            document.getElementById("checkbox_warn").disabled = true;
            document.getElementById("checkbox_notice").disabled=true;
        }
    }