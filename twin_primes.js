importScripts("prime_list.js")

self.addEventListener('message', function(e) {
        var thread_index = e.data[0];
        var workunit_result_uid = e.data[1];
        var start_number = parseInt(e.data[2]);
        var stop_number = parseInt(e.data[3]);
        var version = 1;

        // Check each number and report progress
        function check_number_seq(thread_index, start_number, stop_number) {
                var number, n, nMod;
                var seq_result = [];
                var is_prime;
                var progress = 0;
                var start_n = Math.ceil(start_number / 6);
                var number_dif = stop_number-start_number;
                progress_report_interval=Math.floor(number_dif/100);
                progress_report=start_number;

                // All twin primes except (3,5) are of the form (6n-1,6n+1), so we
                // iterate over n, with number = 6n and checking number-1 and number+1
                for(n=start_n,number=start_n*6; number<=stop_number; n++,number+=6) {
                        // All n such that 6n-1 and 6n+1 are prime must have the units digit 0, 2, 3, 5, 7, or 8
                        nMod = n % 5;
                        if(nMod == 1 || nMod == 4) continue;

                        // Check (6n-1,6n+1)
                        if(check_is_prime(number-1) && check_is_prime(number+1)) {
                            seq_result.push(number-1);
                        }
                        // Report progress
                        if(number > progress_report) {
                                progress=(number-start_number)/number_dif;
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
