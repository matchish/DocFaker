'use strict';

/* Controllers */

angular.module('DocFaker.controllers', []).
    controller('mainCtrl', ['$scope', '$http', '$location', function ($scope, $http, $location) {
        $scope.number = /^\d*$/;
        $http({method: 'GET', url: $location.absUrl(), params: {action: 'get_config'}}).
            success(function (data, status, headers, config) {
                var formatters = [];
                angular.forEach(data.formatters, function (formatters, provider) {
                    angular.forEach(formatters, function (desc, name) {
                        this.formatters.push({name: name, example: desc.example, provider: this.provider, params: desc.params});
                    }, {formatters: this, provider: provider});
                }, formatters);
                data.formatters = formatters;
                $scope.config = data;
            }).
            error(function (data, status, headers, config) {
                // called asynchronously if an error occurs
                // or server returns response with an error status.
            });
    }]).
    controller('treeCtrl', ['$scope', '$http', '$location', function ($scope, $http, $location) {
        $scope.created = 0;
        $scope.errors = [];
        $scope.queue = [];
        $scope.request = false;       
        $scope.doNotDisturb = [];
        $scope.delete = function (data) {
            data.nodes = [];
        };
        $scope.add = function (data) {
            data.nodes.push({amount: null, template: null, nodes: []});
        };
        $scope.desc = function (data, edit) {
            var desc;
            var template;
            if (edit) {
                desc = 'свернуть';
            } else {
                template = $scope.config.templates[data.template];
                if (template !== undefined) {
                    desc = $scope.config.templates[data.template].templatename;
                }
                desc += ' - ' + data.amount;
            }
            return desc;
        };
        $scope.create = function create(parents, nodes) {
            if ($scope.errors.length > 0){
                return;
            }
            for (var i = 0; i < nodes.length; i++) {
                for (var j = 0; j < parents.length; j++) {
                    var doc = {};
                    doc.fields = get_fields(nodes[i].template);
                    doc.fields['parent'] = parents[j];
                    doc.fields['template'] = $scope.config.templates[nodes[i].template].id;
                    doc.fields['isfolder'] = (nodes[i].nodes.length > 0) ? 1 : 0;
                    doc.amount = nodes[i].amount;
                    $scope.queue.push({doc: doc, nodes: nodes[i].nodes});
                }
            }

                    if (!$scope.request){
                    var elem = $scope.queue.pop();
                    if (elem === undefined) {return;}  
                    (function (doc, nodes) {
                        $scope.doNotDisturb.push(true);
                        $scope.request = true;
                        $http({
                            method: 'POST',
                            url: $location.absUrl(),
                            params: {action: 'create_node'},
                            data: doc
                        }).
                            success(function (data, status, headers, config) {
                                $scope.request = false;
                                $scope.doNotDisturb.pop();
                                var parents = data;
                                $scope.created += parents.length;
                                create(parents, nodes);
                            }).
                            error(function (data, status, headers, config) {
                                $scope.request = false;
                                $scope.doNotDisturb.pop();
                                $scope.errors.push("Что-то пошло не так");
                            });
                    })(elem.doc, elem.nodes); 
                    }
                    
            function get_fields(template) {
                var fields = $scope.config.templates[template].fields;
                fields = (fields === undefined) ? {} : fields;
                var result = {};
                angular.forEach(fields, function (field, id) {
                    this[field.name] = field.formatter;
                }, result);
                return result;
            };
        };
        $scope.tree = [
            {root: null, nodes: []}
        ];
    }]).
    controller('configCtrl', ['$scope', '$http', '$location', function ($scope, $http, $location) {
        $scope.save = function () {
            $http({
                method: 'POST',
                url: $location.absUrl(),
                params: {action: 'save_config'},
                data: $scope.$parent.config.templates
            }).
                success(function (data, status, headers, config) {
                    $scope.submit_message = {
                        type: 'alert-success',
                        text: 'Сохранение прошло успешно'
                    };
                }).
                error(function (data, status, headers, config) {
                    $scope.submit_message = {
                        type: 'alert-error',
                        text: 'что-то пошло не так. Попробуйте еще раз'
                    };
                });
        };
    }]);