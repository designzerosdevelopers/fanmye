<div class="pb-2">
    <div class="pb-2 text-center">
        {{ __('Here, you can access detailed insights into the origins and amounts of your earnings.') }}</div>
    <div class="pl-5 pr-5">
        <div class="row">
            <div class="col py-3 text-bold border-bottom">
                <div class="col-lg-12 text-truncate d-md-block text-center">{{ __('Your statistics') }}</div>
            </div>
        </div>

        <div id="statistics" class="d-none" class="card">
            <div class="card-body">
                <style>
                    @keyframes chartjs-render-animation {
                        from {
                            opacity: .99
                        }

                        to {
                            opacity: 1
                        }
                    }

                    .chartjs-render-monitor {
                        animation: chartjs-render-animation 1ms
                    }

                    .chartjs-size-monitor,
                    .chartjs-size-monitor-expand,
                    .chartjs-size-monitor-shrink {
                        position: absolute;
                        direction: ltr;
                        left: 0;
                        top: 0;
                        right: 0;
                        bottom: 0;
                        overflow: hidden;
                        pointer-events: none;
                        visibility: hidden;
                        z-index: -1
                    }

                    .chartjs-size-monitor-expand>div {
                        position: absolute;
                        width: 1000000px;
                        height: 1000000px;
                        left: 0;
                        top: 0
                    }

                    .chartjs-size-monitor-shrink>div {
                        position: absolute;
                        width: 200%;
                        height: 200%;
                        left: 0;
                        top: 0
                    }
                </style>
                
                <div style="width:100%;" class="mt-3 d-none d-sm-block">
                    <div class="chartjs-size-monitor">
                        <div class="chartjs-size-monitor-expand">
                            <div class=""></div>
                        </div>
                        <div class="chartjs-size-monitor-shrink">
                            <div class=""></div>
                        </div>
                    </div>
                    <canvas id="canvas" style="display: block; width: 1379px; height: 689px;" width="1379"
                        height="689" class="chartjs-render-monitor"></canvas>
                </div>

                <form method="post" action="{{ route('statistics.period') }}" id="statisticsForm">
                    @csrf
                    <div class="text-center">
                        <p class="border border-gray rounded-pill p-1 d-flex justify-content-center align-items-center">
                            <span class="mr-3 d-none d-lg-inline">
                                <i class="fas fa-calendar-alt"></i>
                            </span>
                            <span class="text-sm text-nowrap">
                                <small class="d-none d-lg-inline"> From </small><a href="#" class="text-bold text-dark text-decoration-none" id="startDate" onclick="updateStartDate()">01-01-2024</a>
                                <small> To </small><a href="#" class="text-bold text-dark text-decoration-none" id="endDate" onclick="updateEndDate()">31-12-2020</a>
                            </span>
                        </p>
                    </div>
                </form>
                

                <div class="table-responsive">
                    <table class="table">
                        <tbody>
                            <tr class="subscriptions-row">
                                <th>Subscriptions</th>
                                <td class="text-center subscribers_count"></td>
                                <td class="text-right subscribers_amount"></td>
                            </tr>
                            <tr class="tips-row">
                                <th>Tips</th>
                                <td class="text-center tip_count"></td>
                                <td class="text-right tip_amount"></td>
                            </tr>
                            <tr class="ppv-row">
                                <th>PPV</th>
                                <td class="text-center ppv_count"></td>
                                <td class="text-right ppv_amount"></td>
                            </tr>
                            <tr class="messages-row">
                                <th>Messages</th>
                                <td class="text-center messages_count"></td>
                                <td class="text-right messages_amount"></td>
                            </tr>
                            <tr class="total-row">
                                <th>Total</th>
                                <td class="text-center"></td>
                                <td class="text-right total_amount"></td>
                            </tr>
                        </tbody>

                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function updateStartDate() {
            $('#startDate').datepicker({
                format: 'dd-mm-yyyy',
                autoclose: true,
                todayHighlight: true
            }).on('changeDate', function(selected) {
                $(this).text(selected.format('dd-mm-yyyy'));
                ajaxRequest();
            }).datepicker('show');
        }


        function updateEndDate() {
            $('#endDate').datepicker({
                format: 'dd-mm-yyyy',
                autoclose: true,
                todayHighlight: true
            }).on('changeDate', function(selected) {
                $(this).text(selected.format('dd-mm-yyyy'));
                ajaxRequest();
            }).datepicker('show');
        }



        var config = {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Subscriptions',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    fill: false,
                    data: []
                }, {
                    label: 'PPV',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    fill: false,
                    data: []
                }, {
                    label: 'Messages',
                    backgroundColor: 'rgba(255, 206, 86, 0.2)',
                    borderColor: 'rgba(255, 206, 86, 1)',
                    fill: false,
                    data: []
                }, {
                    label: 'Tips',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    fill: false,
                    data: []
                }]
            },
            options: {
                responsive: true,
                title: {
                    display: true,
                    text: 'Chart.js Line Chart - Logarithmic'
                },
                scales: {
                    xAxes: [{
                        display: true,
                        scaleLabel: {
                            display: true,
                            labelString: 'Date'
                        }
                    }],
                    yAxes: [{
                        display: true,
                        scaleLabel: {
                            display: true,
                            labelString: 'Index Returns'
                        },
                        ticks: {
                            min: 0,
                            max: 500,
                            stepSize: 100
                        }
                    }]
                }
            }
        };

        var activeRequests = 0;

        function updateChart() {
            if (activeRequests === 0) {
                if (window.myLine) {
                    window.myLine.destroy();
                }

                var ctx = document.getElementById('canvas').getContext('2d');
                window.myLine = new Chart(ctx, config);
            }
            
                $("#statistics").removeClass("d-none");
        }

        function ajaxRequest() {


            $(function() {
                var startDateValue = $('#startDate').text();
                var endDateValue = $('#endDate').text();

                var formData = {
                    startDate: startDateValue,
                    endDate: endDateValue
                };

                activeRequests++;
                $.ajax({
                    url: "{{ url('/settings/statistics/') }}",
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        console.log(response);

                        // Assuming 'response' contains the data received from the server

                        // Update Subscriptions row
                        $('.subscribers_count').text('(' + response.subs.sold +
                            ' total sold)');
                        $('.subscribers_amount').text('$' + response.subs.amount.toFixed(
                            2));

                        // Update Tips row
                        $('.tip_count').text('(' + response.tips.sold + ' total sold)');
                        $('.tip_amount').text('$' + response.tips.amount.toFixed(2));

                        // Update PPV row
                        $('.ppv_count').text('(' + response.ppv.sold +
                            ' total sold)');
                        $('.ppv_amount').text('$' + response.ppv.amount.toFixed(2));

                        // Update Messages row
                        $('.messages_count').text('(' + response.messages.sold +
                            ' total sold)');
                        $('.messages_amount').text('$' + response.messages.amount.toFixed(
                            2));

                        // Update Total row
                        $('.total_amount').text('$' + response.grand_total_all.toFixed(2));

                        // Extract data from response
                        var statistics = response.statistics;
                        var ppv = response.ppv;
                        var messages = response.messages;
                        var tips = response.tips;
                        var subs = response.subs;
                        var grandTotalAll = response.grand_total_all;

                        // Extract dates from statistics
                        var dates = Object.keys(statistics);

                        // Initialize dataset arrays
                        var subscriptionData = [];
                        var ppvData = [];
                        var messagesData = [];
                        var tipsData = [];

                        // Populate dataset arrays
                        dates.forEach(function(date) {
                            var dataForDate = statistics[date];
                            subscriptionData.push(dataForDate.subscription ? dataForDate
                                .subscription.total_amount : 0);
                            ppvData.push(dataForDate['post-unlock'] ? dataForDate[
                                'post-unlock'].total_amount : 0);
                            messagesData.push(dataForDate.messages ? dataForDate.messages
                                .total_amount : 0);
                            tipsData.push(dataForDate.tip ? dataForDate.tip.total_amount :
                                0);
                        });


                        config.data.labels = dates;
                        config.data.datasets[0].data = subscriptionData;
                        config.data.datasets[1].data = ppvData;
                        config.data.datasets[2].data = messagesData;
                        config.data.datasets[3].data = tipsData;

                        activeRequests--;
                        updateChart();

                    },
                    error: function(xhr, status, error) {
                        console.error(error);
                    }
                });
            });
        };

        window.onload = function() {

            $(function() {
                var today = new Date();
                var dd = String(today.getDate()).padStart(2, '0');
                var mm = String(today.getMonth() + 1).padStart(2, '0');
                var yyyy = today.getFullYear();
                var formattedDate = dd + '-' + mm + '-' + yyyy;
                var endDateElement = $('#endDate');
                endDateElement.text(formattedDate);
            });

            ajaxRequest();

        };
    </script>
