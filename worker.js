self.addEventListener('message', function(e) {
        var thread_index = e.data[0];
        var workunit_result_uid = e.data[1];
        var start_number = parseInt(e.data[2]);
        var stop_number = parseInt(e.data[3]);
        var version = 1;

        function check_number_seq(thread_index, start_number, stop_number) {
                var number;
                var result = [];
                var is_prime;
                var progress = 0;
                progress_report=Math.floor((stop_number-start_number)/100);

                for(number=start_number; number<=stop_number; number++) {
                        is_prime = check_number(number);
                        if(is_prime && check_number(number+2)) {
                                result.push(number);
                                result.push(number+2);
                        }
                        if((number % progress_report) == 0) {
                                progress=(number-start_number)/(stop_number-start_number);
                                self.postMessage([thread_index,0,progress]);
                        }
                }
                return result;
        }

        var result = check_number_seq(thread_index, start_number, stop_number);
        self.postMessage([thread_index, 1, version, workunit_result_uid, result]);
}, false);

function check_number(number) {
        number=parseInt(number);
        var i;
        var limit=Math.floor(Math.sqrt(number));
        for(i=2;i<=limit;i++) {
                if((number%i) == 0) return 0;
        }
        return 1;
}

