Install
========

Copy everything in this folder to churchinfo/.

Onboarding
==========
- [PHP Programming & HOC3 ChurchInfo](https://docs.google.com/presentation/d/1YyWXjUEH5sB93nfboS0woosr5-lYBvdWVpNHIbqXgpE/edit#slide=id.p)

Usage
=====

Data/Reports -> Reports Menu -> Contacts Book 2014 Style

Hide Fields In 2014 Style Reporting
===================================

### Hide Family Address and Phone
- First add a family property "hide_address" and "hide_phone" through "Properties" -> "Family Properties".
- Then in family page, add property "hide_address" or "hide_phone"

## Hide Person Phone or Email
- First add person property "hide_email" and "hide_phone" through "Properties" -> "People Properties"
- Then in person page, add property "hide_email" or "hide_phone". It's OK to add multiple properties.

Select Person/Family Appeared in Both Groups
============================================

First make sure the people and the family belongs to two groups. 
Then in "Contact Book 2014 Style" page, select one group in "Group Membership" and another group in "Group Membership 2".

Make a ZIP Package
==================

git archive -o hoc3_contacts_php.zip HEAD

Contributors
============

Jin Zhou <ferryzhou@gmail.com>
David Guo <davidguo94@gmail.com>

