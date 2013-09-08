'use strict';

/* Services */


angular.factory('httpQueue', function($q, $http) {

    var queue = [];
    var execNext = function() {
        var task = queue[0];
        $http(task.c).then(function(data) {
            queue.shift();
            task.d.resolve(data);
            if (queue.length > 0) execNext();
        }, function(err) {
            task.d.reject(err);
        });
    };
    return function(config) {
        var d = $q.defer();
        queue.push({
            c: config,
            d: d
        });
        if (queue.length === 1) execNext();
        return d.promise;
    };

});