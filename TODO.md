## TODO:

1. Make the output format selectable like with the siteid, so that after selection we can switch between the two lines below for different output presentation. Possible options are ~~PHP array/json~~/table:
    - ~~print_r ($data);~~
    - ~~echo json_encode($data, JSON_PRETTY_PRINT);~~
    - or use DataTables for table output

2. Add the option to export data to CSV file format
    - need to have the option to select which fields to export
    - could be combined with the DataTables output option

3. Add simple user/password login
    - consider setting user name/password in the config file, though
      not very secure
    - avoid dependencies such as MySQL etc. as much as possible
