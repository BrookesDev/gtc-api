
<script>
    $(document).ready(function() {

        /* When click delete button */
        $('body').on('click', '#deleteRecord', function() {
            var step_id = $(this).data('id');
            // var url = $(this).data('url');
            url = $("#deleteURL").html();
            var token = $("meta[name='csrf-token']").attr("content");
            var el = this;
            // alert(step_id);
            resetAccount(el, step_id, url);
        });


        async function resetAccount(el, step_id, url) {
            const willUpdate = await new swal({
                title: "Confirm Action",
                text: `Are you sure you want to delete this record?`,
                icon: "warning",
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes!",
                showCancelButton: true,
                buttons: ["Cancel", "Yes, Delete"]
            });
            // console.log(willUpdate);
            if (willUpdate) {
                //performReset()
                performDelete(el, step_id, url);
            } else {
                new swal("Record will not be deleted  :)");
            }
        }


        function performDelete(el, step_id,url) {
            //alert(step_id);
            try {
                $.get(url+'?id=' + step_id,
                    function(data, status) {
                        console.log(status);
                        console.table(data);
                        if (status === "success") {
                            let alert = new swal("Record successfully deleted!.");
                            $(el).closest("tr").remove();
                            // alert.then(() => {
                            // });
                        }
                    }
                );
            } catch (e) {
                let alert = new swal(e.message);
            }
        }





    });
</script>

