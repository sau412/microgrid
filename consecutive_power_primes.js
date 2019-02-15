// Project to search for consecutive primes p_1 ... p_k
// such that p_1^n + ... + p_k^n is a perfect nth root
// See https://www.primepuzzles.net/puzzles/puzz_393.htm
// and https://www.primepuzzles.net/puzzles/puzz_443.htm

// BigInteger.js library v1.6.40 (https://github.com/peterolson/BigInteger.js)
importScripts("BigInteger.min.js")

self.addEventListener('message', function(e) {
  var thread_index = e.data[0];
  var workunit_result_uid = e.data[1];
  var start_number = bigInt(e.data[2]);
  var stop_number = bigInt(e.data[3]);
  var version = 1;

  var LOW_EXPONENT = 2, HIGH_EXPONENT = 6, MAX_PRIME_SEQUENCE_SIZE = 50;

  // Check each number and report progress
  function check_number_seq(thread_index, start_number, stop_number) {
    var next_prime, next_prime_power;
    var prime_sequences = [], prime_sums = [];
    var max_power = 10, power_limit;
    var seq_result = [];
    var validation_hash = bigInt.zero, temp_hash;
    var progress = 0;
    size = stop_number.minus(start_number).toJSNumber();
    progress_report_interval=size / 100;
    progress_report=start_number;
    for(let i = 0; i <= MAX_PRIME_SEQUENCE_SIZE; i++) {
      prime_sequences[i] = [];
      prime_sums[i] = [];
      for(let e = LOW_EXPONENT; e <= HIGH_EXPONENT; e++) {
        prime_sequences[i][e] = [];
        prime_sums[i][e] = bigInt.zero;
      }
    }

    if(start_number.isEven()) start_number = start_number.plus(1);

    // Find previous prime to make sure we don't miss a matching
    // consecutive prime sequence that spans multiple steps
    {
      let i, pnum = start_number, j, rnum;
      for(i = 0; i < MAX_PRIME_SEQUENCE_SIZE; i++) {
        pnum = find_previous_prime(pnum);
        for(e = LOW_EXPONENT; e <= HIGH_EXPONENT; e++) {
          rnum = pnum.pow(e)
          for(j = i+1; j <= MAX_PRIME_SEQUENCE_SIZE; j++) {
            prime_sequences[j][e].unshift(rnum);
            prime_sums[j][e] = prime_sums[j][e].plus(rnum);
          }
        }
      }
    }

    next_prime = find_next_prime(start_number, stop_number);

    while(next_prime != null) {
      for(let e = LOW_EXPONENT; e <= HIGH_EXPONENT; e++) {
        next_prime_power = next_prime.pow(e);
        for(let i = 2; i <= MAX_PRIME_SEQUENCE_SIZE; i++) {
          prime_sums[i][e] = prime_sums[i][e].minus(prime_sequences[i][e].shift()).plus(next_prime_power);
          prime_sequences[i][e].push(next_prime_power);
          if(prime_sums[i][e].nthRoot(e).pow(e).equals(prime_sums[i][e])) {
            // Found something!
            seq_result.push({ "primes": prime_sequences[i][e].map(x => x.nthRoot(e).toString()), "exponent": e, "sum": prime_sums[i][e].toString(), "is_prime": prime_sums[i][e].nthRoot(e).isPrime() });
          }
          validation_hash = validation_hash.plus(prime_sums[i][e].divide(37).mod(Number.MAX_SAFE_INTEGER));
        }
      }

      // Report progress
      if(next_prime.greater(progress_report)) {
        progress=next_prime.minus(start_number).toJSNumber() / size;
        self.postMessage([thread_index,0,progress]);
        progress_report=progress_report.plus(progress_report_interval);
      }
      next_prime = find_next_prime(next_prime.plus(2), stop_number);
    }

    validation_hash = validation_hash.mod(Number.MAX_SAFE_INTEGER);

    // Return result
    return { "results": seq_result, "validation_hash": validation_hash.toJSNumber() };
  }

  var result = check_number_seq(thread_index, start_number, stop_number);
  // Return result with message to parent thread
  self.postMessage([thread_index, 1, version, workunit_result_uid, result]);
}, false);

// Includes start_number and stop_number in search
function find_next_prime(start_number, stop_number) {
  for(let number=start_number; number.lesserOrEquals(stop_number); number = number.plus(2)) {
    // Check number
    if(number.isPrime()) {
      return number;
    }
  }
  return null;
}

// Excludes number in search
function find_previous_prime(number) {
  if(number.lesser(4)) return null;
  for(number = number.minus(bigInt.one);!number.isPrime();number=number.minus(bigInt.one));
  return number;
}

// Based on implementation of Newton's method from https://stackoverflow.com/questions/15978781/how-to-find-integer-nth-roots
// Finds nth root of k
bigInt.prototype.nthRoot = function (n) {
  k = this;
  n = bigInt(n)
  var nm = n.minus(bigInt.one);
  var u = k;
  var s = k.plus(bigInt.one);
  while(u.lesser(s)) {
    s = u
    u = nm.multiply(s).plus(k.divide(s.pow(nm))).divide(n)
  }
  return s;
};
