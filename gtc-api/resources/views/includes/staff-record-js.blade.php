
<script>
    $(document).ready(function() {

        function appendRecord(record, index) {
            tbodyEl.append(`
                            <tr>
                            <td>${index + 1}</td>
                            <td><a href="/admin/staff/view/${record.id}">${record.staff_id }</a></td>
                            <td>${record.title }</td>
                            <td><a href="/admin/staff/view/${record.id}">${record.lastname} ${record.firstname } ${record.middlename }</a></td>
                            <td>${record.phone_number }</td>
                            <td>${record.email }</td>
                            <td>${record.address }</td>
                            <td>${record.qualification }</td>
                            <td>${record?.grade?.description ?? '' }</td>
                            <td>${record?.step?.description ?? ''}</td>
                            <td>${record?.level?.description ?? '' }</td>

                            <td class="text-end">
                                <a  data-id="${record.id}"
                                     href="/admin/staff/view/${record.id}"
                                    class="btn btn-success-soft btn-sm mr-1"><i class="far fa-eye"></i></a>

                                <a href="javascript:void(0);" class="btn btn-danger-soft btn-sm"
                                    id="deleteRecord" data-id="${record.id}">
                                    <i class="far fa-trash-alt"></i></i></a>
                                </td>
                            </tr>
                        `);
        }
        function appendRecordsInChunks(records, chunkSize, delay, callback) {
    let index = 0;
    const interval = setInterval(() => {
        const endIndex = Math.min(index + chunkSize, records.length);
        const chunk = records.slice(index, endIndex);
        chunk.forEach((record, chunkIndex) => {
            appendRecord(record, index + chunkIndex);
        });
        index += chunkSize;
        if (index >= records.length) {
            clearInterval(interval);
            $('#fileValidate').show();
            if (typeof callback === 'function') {
                callback();
            }
        }
    }, delay);
}


function loadRecords(dirUrl) {

    $.ajax({
        url:  dirUrl,
        type: "GET",
        dataType: "json",
        success: function(response) {
            var len = 0;
            if (response['data'] != null) {
                len = response['data'].length;
            }
            tbodyEl = $("#dataBody");
            tbodyEl.empty()
            $('.table').DataTable().destroy();
            if (len == 0) {
                $('.table').DataTable({
                    // "order": [[ 6, "desc" ]],
                    pageLength: 50,
                    scrollY: 500,
                    scrollCollapse: true,
                    paginate: true
                });
                swal("No Record Found !");
            } else {

                const chunkSize = 3000; // Number of records to append in each chunk
                const delay = 50; // Delay between each chunk (in milliseconds)
                records = response['data'];
                appendRecordsInChunks(records, chunkSize, delay, () => {
                    // This function will be executed after all records have been appended
                    if ($.fn.dataTable.isDataTable('.table')) {
                        table = $('.table').DataTable();
                    } else {
                        table = $('.table').DataTable({
                            // paging: false
                        });
                    }
                });
            }
            $("#loaderPage").hide();
        }
    })
}

    var thisRecordURL = $('#recordURL').html();
        loadRecords(thisRecordURL);


        //when user clicks on edit button
        $('body').on('click', '#editButton', function() {
            // alert("Nathaniel")
            var id = $(this).data('id');
            var description = $(this).data('description');
            $('#recordID').val(id);
            $('#description').val(description);

        });


    });
</script>
