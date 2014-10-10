@mod @mod_dataform @dataformfield @dataformfield_duration @dataformfieldtest
Feature: Add dataform entries

    @javascript
    Scenario: Use required or noedit patterns
        Given I start afresh with dataform "Test duration field"

        And I log in as "teacher1"
        And I follow "Course 1"
        And I follow "Test duration field"

        ## Field
        And I go to manage dataform "fields"
        And I add a dataform field "duration" with "Duration"

        ## View
        And I go to manage dataform "views"
        And I add a dataform view "aligned" with "View 01"
        And I set "View 01" as default view

        # No rules no content
        And I follow "Browse"
        And I follow "Add a new entry"
        And I press "Save"
        Then "id_editentry1" "link" should exist

        # No rules with content
        And I follow "id_editentry1"
        And I set the field "id_field_1_1_number" to "61"
        And I press "Save"
        Then I see "61 minutes"

        When I follow "id_editentry1"
        And I set the field "id_field_1_1_number" to ""
        And I press "Save"
        Then I do not see "61 minutes"

        # Required *
        When I go to manage dataform "views"
        And I follow "id_editview1"
        And I expand all fieldsets
        And I replace in field "Entry template" "[[Duration]]" with "[[*Duration]]"
        And I press "Save changes"
        And I follow "Browse"
        And I follow "id_editentry1"
        And I set the field "id_field_1_1_number" to ""
        And I set the field "id_field_1_1_timeunit" to "minutes"
        Then I see "You must supply a value here."
        And I set the field "id_field_1_1_number" to "53"
        And I press "Save"
        Then I see "53 minutes"

        # No edit !
        When I go to manage dataform "views"
        And I follow "id_editview1"
        And I expand all fieldsets
        And I replace in field "Entry template" "[[*Duration]]" with "[[!Duration]]"
        And I press "Save changes"
        And I follow "Browse"
        And I follow "id_editentry1"
        Then "id_field_1_1_number" "field" should not exist
        And I press "Save"
        Then I see "53 minutes"
