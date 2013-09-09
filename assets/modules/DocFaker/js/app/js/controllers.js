'use strict';

/* Controllers */

angular.module('DocFaker.controllers', []).
controller('mainCtrl', ['$scope', 'httpQueue', '$location',
    function($scope, httpQueue, $location) {
        $scope.number = /^\d*$/;
        
        httpQueue({
            method: 'GET',
            url: $location.absUrl(),
            params: {
                action: 'get_config'
            }
        }).
        then(function(data) {
            data = data.data;
            var formatters = [];
            angular.forEach(data.formatters, function(formatters, provider) {
                angular.forEach(formatters, function(desc, name) {
                    this.formatters.push({
                        name: name,
                        example: desc.example,
                        provider: this.provider,
                        params: desc.params
                    });
                }, {
                    formatters: this,
                    provider: provider
                });
            }, formatters);
            data.formatters = formatters;
            $scope.config = data;
        });
    }
]).
controller('treeCtrl', ['$scope', 'httpQueue', '$location',
    function($scope, httpQueue, $location) {        
        
        $scope.created = 0;
        $scope.must_be_created = 0;
        $scope.errors = [];
        
        $scope.init = function(){
            $scope.created = 0;
            $scope.must_be_created = 0;
            $scope.errors = [];         
        }
            
        $scope.doNotDisturb = function(){
            return !($scope.must_be_created == $scope.created || $scope.errors.length > 0)
        };          
    
        $scope.delete = function(data) {
            data.nodes = [];
        };
        
        $scope.add = function(data) {
            data.nodes.push({
                amount: null,
                template: null,
                nodes: []
            });
        };
        
        $scope.desc = function(data, edit) {
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
            
            if ($scope.errors.length > 0) {
                return;
            }           
            
            angular.forEach(nodes, function(node) {
                angular.forEach(parents, function(parent) {
                    var node = this;
                    var doc = {};
                    doc.fields = get_fields(node.template);
                    doc.fields['parent'] = parent;
                    doc.fields['template'] = $scope.config.templates[node.template].id;
                    doc.fields['isfolder'] = (node.nodes.length > 0) ? 1 : 0;
                    doc.amount = node.amount;
                    
                    $scope.must_be_created += parseInt(doc.amount);
                    
                    httpQueue({
                        method: 'POST',
                        url: $location.absUrl(),
                        params: {
                            action: 'create_node'
                        },
                        data: doc
                    }).
                    then(function(data) {
                        var parents = data.data;
                        $scope.created += parents.length;
                        create(parents, node.nodes);
                    },function(data) {
                        $scope.errors.push("Что-то пошло не так");
                    });

                }, node);
            });

            function get_fields(template) {
                var fields = $scope.config.templates[template].fields;
                fields = (fields === undefined) ? {} : fields;
                var result = {};
                angular.forEach(fields, function(field, id) {
                    this[field.name] = field.formatter;
                }, result);
                return result;
            };
        };
        
        $scope.tree = [{
            root: null,
            nodes: []
        }];
    }
]).
controller('configCtrl', ['$scope', 'httpQueue', '$location',
    function($scope, httpQueue, $location) {
        $scope.save = function() {
            httpQueue({
                method: 'POST',
                url: $location.absUrl(),
                params: {
                    action: 'save_config'
                },
                data: $scope.$parent.config.templates
            }).
            then(function(data) {
                $scope.submit_message = {
                    type: 'alert-success',
                    text: 'Сохранение прошло успешно'
                };
            }, function(data) {
                $scope.submit_message = {
                    type: 'alert-error',
                    text: 'что-то пошло не так. Попробуйте еще раз'
                };
            });
        };
    }
]);