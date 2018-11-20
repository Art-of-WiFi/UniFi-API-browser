## TODO:

1. Make the output format selectable like with the siteid, so that after selection we can switch between the two lines below for different output presentation. Possible options to add are ~~PHP array/json~~/table/chart:
    - ~~print_r ($data);~~
    - ~~echo json_encode($data, JSON_PRETTY_PRINT);~~
    - DataTables for table output and more
        - requires "flattening" of multidimensional arrays/objects
        - or else only expose a selection of attributes
    - Chart output as option for certain data collections (and selected attributes)

2. Add the option to export data to CSV file format
    - need to have the option to select which fields to export
    - could be combined with the DataTables output option
    - requires "flattening" of multidimensional arrays/objects

3. ~~Add simple user/password login~~
    - ~~consider setting user name/password in the config file, though not very secure~~
    - ~~avoid dependencies such as MySQL etc. as much as possible~~

### Call for suggestions

If you have any suggestions on how to further improve this tool, please open an issue on GitHub. All feedback is welcome!
