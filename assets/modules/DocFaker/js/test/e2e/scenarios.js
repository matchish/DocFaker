'use strict';

/* http://docs.angularjs.org/guide/dev_guide.e2e-testing */

describe('DocFaker', function() {

  beforeEach(function() {
    browser().navigateTo('../../app/index.html');
  });


  it('should automatically redirect to /tree when location hash/fragment is empty', function() {
    expect(browser().location().url()).toBe("/tree");
  });

  it('should content 2 buttons in menu', function() {
    expect(element('#menu li').count()).toBe(2);
  });

  it('should deactivate button when location hash equal href of button', function() {
    browser().navigateTo('#/settings');
    var button = element('[href="#/settings"]');
    expect(button.attr('class')).
      toMatch(/disabled/);
  });

  describe('tree', function() {

    beforeEach(function() {
      browser().navigateTo('#/tree');
    });


    it('should render tree when user navigates to /tree', function() {
      expect(element('[ng-view] p:first').text()).
        toMatch(/tree/);
    });
  });


  describe('settings', function() {

    beforeEach(function() {
      browser().navigateTo('#/settings');
    });


    it('should render settings when user navigates to /settings', function() {
      expect(element('[ng-view] p:first').text()).
        toMatch(/settings/);
    });

  });
});
