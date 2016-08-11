Kaliber5BehatBundle
===================

JsonMinkContext
---------------
### Use JsonMinkContext

If you use a Context class that extends the JsonMinkContext, the class will search for files relative to the folder the class is in '../JsonResponse' and '../JsonRequest'.
 
If you use JsonMinkContext as Context for your suite, you can add the bundle name in the constructor where your json-files live in.


    // e.g. behat.yml 
    suites:
       gem.api:
         type: symfony_bundle
         bundle: AppBundle
         contexts:
            - AppBundle\Features\Context\DBContext
            - Kaliber5\BehatBundle\Context\JsonMinkContext:
               bundle: 'AppBundle'

In this case the JsonMinkContext will search for the json files in the 'AppBundle/Features/JsonRequest' and 'AppBundle/Features/JsonResponse' folders


### Handle Resources

By default JsonMinkContext can handle resources like list, show and filter:

    Feature: Gem Api
      In order to implement a service
      As an api client
      I want to be able to list and show the gems
    
      Background:
        Given there are gems         
    
      Scenario: get list of gems
        When I go to the "gems" resources
        Then I should see a valid jsonapi response
        And I will see the resources as json
        
      Scenario: Get single gem
        When I go to the "gems" resource with id 1
        Then I should see a valid jsonapi response
        And I will see the resource as json         
        
      Scenario: Filter list of gems
        When I filter the "gems" resources with:
          | property | value     |
          | carat    | {">": 50} |
        Then I should see a valid jsonapi response
        And I will see the resources filtered by "carat"
        
      Scenario: Filter list of gems
        When I filter the "gems" resources with:
          | property | value     |
          | carat    | {">": 50} |
        Then I should see a valid jsonapi response
        And I will see the resources filtered by "carat" with value "50"

     
The step

    When I go to the "gems" resources
    
results in a GET "/api/gems" Request and will store "gems" as the last requested resource. After then
 
    And I will see the resources as json

will compare the Response contents with the File 'gems_get.json' in the JsonResponse-folder.


The step

    When I go to the "gems" resource with id 1
    
results in a GET "/api/gems/1" Request and will store "gems" and 1 as the last requested resource and id. After then
 
    And I will see the resource as json

will compare the Response contents with the File 'gems_get_1.json' in the JsonResponse-folder.

The step

    When I filter the "gems" resources with ...
    
results in a GET "/api/gems?filter[carat]={">": 50}" Request and will store "gems" as the last requested resource. After then
 
    And I will see the resources filtered by "carat"

will compare the Response contents with the File 'gems_get_filtered_carat.json' in the JsonResponse-folder.

    And I will see the resources filtered by "carat" with value "50"

will compare the Response contents with the File 'gems_get_filtered_carat.json' in the JsonResponse-folder and will replace the token '#carat#' with 50.

