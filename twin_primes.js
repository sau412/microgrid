importScripts("prime_list.js")

self.addEventListener('message', function(e) {
        var thread_index = e.data[0];
        var workunit_result_uid = e.data[1];
        var start_number = parseInt(e.data[2]);
        var stop_number = parseInt(e.data[3]);
        var version = 1;

        // Check each number and report progress
        function check_number_seq(thread_index, start_number, stop_number) {
                var number;
                var seq_result = [];
                var is_prime;
                var progress = 0;
                progress_report_interval=Math.floor((stop_number-start_number)/100);
                progress_report=start_number;

                if((start_number % 2) == 0) start_number++;

                for(number=start_number; number<=stop_number; number+=4) {
                        // Check number
                        if(check_is_prime(number)) {
                          if(check_is_prime(number+2)) {
                            // Add number to results
                            seq_result.push(number);
                          }
                          else {
                            // Optimization - number+2 is false so we can skip it
                            number+=2;
                          }
                          if(check_is_prime(number-2)) {
                            // Add number to results
                            seq_result.push(number-2);
                          }
                        }
                        // Report progress
                        if(number > progress_report) {
                                progress=(number-start_number)/(stop_number-start_number);
                                self.postMessage([thread_index,0,progress]);
                                progress_report+=progress_report_interval;
                        }
                }
                // Return result
                return seq_result;
        }

        var result = check_number_seq(thread_index, start_number, stop_number);
        // Return result with message to parent thread
        self.postMessage([thread_index, 1, version, workunit_result_uid, result]);
}, false);

// Check is number prime or not
function check_is_prime(number) {
        number=parseInt(number);
        var i, list_bound;
        var limit=Math.floor(Math.sqrt(number));

        // Based off of isprime implementation here: https://github.com/ExclusiveOrange/IsPrime/blob/master/isprime.hpp
        for(list_bound=PRIME_LIST.length; list_bound >= 0 && PRIME_LIST[list_bound-1] > limit; list_bound--);
        for(i=0; i < list_bound; i++) {
                if((number%PRIME_LIST[i]) == 0) return 0;
        }

        for(i=PRIME_LIST[PRIME_LIST.length-1]+2;i<=limit;i+=2) {
                if((number%i) == 0) return 0;
        }
        return 1;
}
