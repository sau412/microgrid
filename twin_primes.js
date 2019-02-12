self.addEventListener('message', function(e) {
        var thread_index = e.data[0];
        var workunit_result_uid = e.data[1];
        var start_number = parseInt(e.data[2]);
        var stop_number = parseInt(e.data[3]);
        var version = 1;
        function check_number_seq(thread_index, start_number, stop_number) {
                var number;
                var seq_result = [];
                var is_prime;
                var progress = 0;
                progress_report_interval=Math.floor((stop_number-start_number)/100);
                progress_report=start_number;
                if((start_number % 2) == 0) start_number++;
                for(number=start_number; number<=stop_number; number+=2) {
                        result = check_number(number);
                        if(result !== null) {
                                seq_result.push(number);
                                number+=4;
                        }
                        if(number > progress_report) {
                                progress=(number-start_number)/(stop_number-start_number);
                                self.postMessage([thread_index,0,progress]);
                                progress_report+=progress_report_interval;
                        }
                }
                return seq_result;
        }
        var result = check_number_seq(thread_index, start_number, stop_number);
        self.postMessage([thread_index, 1, version, workunit_result_uid, result]);
}, false);
function check_number(number) {
        if((number%2)==0) return null;
        if(check_is_prime(number) && check_is_prime(number+2)) return [number,number+2];
        return null;
}
function check_is_prime(number) {
        number=parseInt(number);
        var i;
        var limit=Math.floor(Math.sqrt(number));
        for(i=2;i<=limit;i++) {
                if((number%i) == 0) return 0;
        }
        return 1;
}
