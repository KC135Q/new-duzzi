<!DOCTYPE html>
<head></head>
<body>
    <div id="dvImportSegments" class="fileupload ">
        <fieldset>
            <legend>Upload your CSV File</legend>
            <input type="file" name="File Upload" id="txtFileUpload" accept=".csv" />
        </fieldset>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.0.0-alpha1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-csv/0.71/jquery.csv-0.71.min.js"></script>
    <script type="text/javascript">  
$(document).ready(function() {
    // Keywords = post_excerpt, Description = post_content
    var columnNames = {'filename': 0, 'post_excerpt': 1, 'post_content': 2,};

    // The event listener for the file upload
    document.getElementById('txtFileUpload').addEventListener('change', upload, false);

    // Method that checks that the browser supports the HTML5 File API
    function browserSupportFileUpload() {
        var isCompatible = false;
        if (window.File && window.FileReader && window.FileList && window.Blob) {
            isCompatible = true;
        }
        return isCompatible;
    }

    // Set the filenameColumn to the row that contains the filename in the column - only called before you build the product file and the variation file
    function setFilenameColumns(data) {  
        // Split the header row into an array
        var headers = data[0];
        // iterate through the array to see which index contains the FileName
        for (header in headers) {
            switch(headers[header]) {
                case('FileName'):
                    columnNames['filename'] = parseInt(header, 10);
                    break;
                case('Keywords'):
                    columnNames['post_excerpt'] =parseInt( header, 10);
                    break;
                case('Description'):
                    columnNames['post_content'] = parseInt(header, 10);
                    break;
                default:
                    break;            
            }
            // if (headers[header] == 'FileName') {
            //     filenameColumn = header;
            //     console.log("Found the filename in column " + filenameColumn);
            // }
        }
        console.log(columnNames);
    }

    function buildProductFile(data) {
        // Identify Filename Column (array index)
        // Parse Filename into prefix (number & name) and filetype
        // post_title becomes name {space} number
        // All we need in the input file is FileName, Keywords, and Description
        // post_excerpt = Keywords
        // post_content = Description
        // post_name = prefix only
        // sku = number
        // post_title, post_name, post_excerpt, post_content, sku, attribute:Material, attribute_data:Material, attribute:Size, attribute_data:Size
        // attribute:Material = Print | Digital High Resolution | Canvas (rolled in a tube)
        // attribute_data:Material = 0 | 0 | 1
        // attribute:Size = 8x12 (20x30cm) | 12x18 (30x45cm) | 16x24 (40x60cm) | 20x28 (50x70cm) | 24x36 (60x90cm)
        // CSV Order = 24x36 (60x90cm), 12x18 (30x45cm), 16x24 (40x60cm), 20x28 (50x70cm), 8x12 (20x30cm), 12x18 (30x45cm), 16x24 (40x60cm), 20x28 (50x70cm), 24x36 (60x90cm), 8x12 (20x30cm), 12x18 (30x45cm), 16x24 (40x60cm), 20x28 (50x70cm)
        // attribute_data:Size 
        setFilenameColumns(data);
        // console.log("Filename column: "+ filenameColumn);
        var productString = 'post_title,tax:product_type,images/gallery,category,post_tag,post_excerpt,sku,attribute:material,attribute_data:material,attribute:size,attribute_data:size\n';
        for (rows in data) {
            if (rows != 0) {
                tmpFileName = data[rows][columnNames['filename']];
                fileName = tmpFileName.replace(" ", "-");
                console.log("Filename: " + tmpFileName);
                nameStart = tmpFileName.lastIndexOf('-');
                extStart = tmpFileName.lastIndexOf('.');
                tmpSKU =  "DA-" + tmpFileName.substring(0, nameStart );
                tmpName = tmpFileName.substring(nameStart+1, extStart).replace(/\b\w/g, l => l.toUpperCase());
                console.log("Title: " + tmpName + " " + tmpSKU);
                productString += tmpName + " " + tmpSKU + ",variable," + fileName + "," + tmpName + "," + data[rows][columnNames['post_excerpt']] + "," + data[rows][columnNames['post_content']];
                productString += "," + tmpSKU  + "," + "Print | Digital High Resolution | Canvas (rolled in a tube), 0 | 0 | 1, 8x12 (20x30cm) | 12x18 (30x45cm) | 16x24 (40x60cm) | 20x28 (50x70cm) | 24x36 (60x90cm), 1|0|1 \n";
            }
        }
        return productString;        
    }

    function buildVariationFile(data) {
        var printOptions = ['75,Print,24x36 (60x90cm)', '50,Canvas (rolled in a tube),12x18 (30x45cm)', '65,Canvas (rolled in a tube),16x24 (40x60cm)', '85,Canvas (rolled in a tube),20x28 (50x70cm)', '20,Digital High Resolution,8x12 (20x30cm)', '30,Digital High Resolution,12x18 (30x45cm)', '40,Digital High Resolution,16x24 (40x60cm)', '55,Digital High Resolution,20x28 (50x70cm)', '70 ,Digital High Resolution,24x36 (60x90cm)', '25,Print,8x12 (20x30cm)', '35,Print,12x18 (30x45cm)', '45,Print,16x24 (40x60cm)', '60,Print,20x28 (50x70cm)'];

        // Identify Filename Column === filenameColumn global variable
        // console.log("Filename column2: "+ filenameColumn);
        // parent_sku = number
        // regular price 75, 50, 65, 85, 20, 30, 40, 55, 70, 25, 35, 45, 60
        // meta:attribute_material:     Print,  Canvas(rolled in a tube) (x3), Digital High Resolution (x5), Print (x4)
        // meta:attribute_size:
        // productString = "parent_sku, "
        var productString = 'parent_sku,sku,regular_price,meta:attribute_material,meta:attribute_size\n'; 
        for (rows in data) {
            if (rows != 0) {
                tmpFileName = data[rows][columnNames['filename']];
                nameStart = tmpFileName.lastIndexOf('-');
                tmpSKU =  "DA-" + tmpFileName.substring(0, nameStart );
                skuCtr = 1;
                for (pOption in printOptions) {
                    productString += tmpSKU + "," + tmpSKU + "-" + skuCtr + "," + printOptions[pOption] + "\n";
                    skuCtr++;
                }
            }
        }
        return productString;        
    }

    // Method that reads and processes the selected file
    function upload(evt) {
        if (!browserSupportFileUpload()) {
            alert('The File APIs are not fully supported in this browser!');
        } else {
            var data = null;
            var file = evt.target.files[0];
            var reader = new FileReader();
            reader.readAsText(file);
            reader.onload = function(event) {
                var csvData = event.target.result;
                data = $.csv.toArrays(csvData);
                if (data && data.length > 0) {
                    alert('Imported -' + data.length + '- rows successfully!');
                    // Build the product common separated value files
                    pcsvf = buildProductFile(data);
                    // Make the product file available for download
                    download('myProducts.csv', pcsvf);
                    // Build the product variation files
                    vcsvf = buildVariationFile(data);
                    // Make the variation file available for download
                    download('myVariations.csv', vcsvf);
                } else {
                    alert('No data to import!');
                }
            };
            reader.onerror = function() {
                alert('Unable to read ' + file.fileName);
            };
        }
    }

    function download(fileName = 'myFileName.csv', csv = 'data1, data2, data3\r\ndata4,data5,data6\r\ndata7,data8,data9\r\n') {
        a=document.createElement('a');
        a.textContent='Download ' + fileName;
        a.download=fileName;
        a.href='data:text/csv;charset=utf-8,'+encodeURIComponent(csv);
        d=document.createElement('div');
        d.appendChild(a);
        document.body.appendChild(d);
    }
});
</script>    
</body>
</html>