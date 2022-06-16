# magento-plugin
Reach plugin for Magento v2.2 & v2.3.

If you are interested in using this plugin please contact sales@withreach.com

# Deployment Pipeline
1. work on dev feature branch
2. open PR, merging to QA branch
3. PR approved
4. merge to QA branch
5. delete feature branch
6. deploy to QA environment
   1. Access QA Server
   2. run `bin/composer install`
   3. which will pull the qa branch code into the magento site with recent updates
7. QA process complete
8. merge QA to Master
9. Release to Packagist
   1. Update `composer.json` with new version
   2. [Create and publish release](https://docs.github.com/en/repositories/releasing-projects-on-github/managing-releases-in-a-repository) on github