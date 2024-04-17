<?php

$input_dir = "data/in/";
$output_file = "data/out/data.done.dat";

$customers = [];
$sellers = [];
$sales = [];

$biggestSale = [];
$biggestSaleValue = 0;

function getData() {

  global $input_dir;
  global $customers;
  global $sellers;
  global $sales;

  $dir = opendir($input_dir);
  
  while (($file = readdir($dir)) !== false) {
    if (is_file($input_dir . "/" . $file)) {
      $file_content = file_get_contents($input_dir . "/" . $file);

      $lines = explode("\n", $file_content);
      foreach ($lines as $line) {
        $data = explode("ç", $line);

        switch ($data[0]) {
          case "001":
            $seller = [
              "CPF" => $data[1],
              "Nome" => $data[2],
              "Salary" => isset($data[3]) ? $data[3] : null,
              "Total_De_Vendas" => 0,
            ];
            $sellers[] = $seller;
            break;

          case "002":
            $customer = [
              "CNPJ" => $data[1],
              "Nome" => $data[2],
              "Ramo_de_Atividade" => isset($data[3]) ? $data[3] : null,
            ];
            $customers[] = $customer;
            break;

          case "003":
            $sale = [
              "ID_Venda" => $data[1],
              "Nome_vendedor" => $data[3],
              "itens" => handleSaleItens($data[2], $data[1]),
            ];
            $sales[] = $sale;

            $valorTotalVenda = 0;
            foreach ($sale["itens"] as $item) {
              $valorTotalVenda += $item["Qtd"] * $item["Preco"];
            }

            foreach ($sellers as &$seller) {
              if ($seller["Nome"] === $sale["Nome_vendedor"]) {
                $seller["Total_De_Vendas"] += $valorTotalVenda;
                break;
              }
            }
            break;
        }
      }
    }

  }

  closedir($dir);
}

function handleSaleItens($itens_arr, $sale_id) {
  $itens = [];
  $item_data = explode("[", $itens_arr);
  $item_data = explode("]", $item_data[1]);

  global $biggestSaleValue;
  global $biggestSale;

  foreach (explode(",", $item_data[0]) as $item_info) {
    $item = explode("-", $item_info);
    $itens[] = [
      "ID_Item" => $item[0],
      "Qtd" => $item[1],
      "Preco" => $item[2],
    ];

    if($item[1] * $item[2] > $biggestSaleValue) {
      $biggestSaleValue = $item[1] * $item[2];
      $biggestSale = 
      [
        "ID_Venda" => $sale_id,
        $itens,
      ];
    }
  }

  return $itens;
}

function findWorstSeller() {
  global $sellers;

  usort($sellers, function ($a, $b) {
    return $a["Total_De_Vendas"] - $b["Total_De_Vendas"];
  });
  
  return $sellers[0]["Nome"];
}

function outputData() {
  global $output_file;
  global $biggestSale;
  global $customers;
  global $sellers;

  $content = "RELATÓRIO\n\n";
  $content .= "Quantidade de clientes no arquivo: " . count($customers) . "\n";
  $content .= "Quantidade de vendedores no arquivo: " . count($sellers) . "\n";
  $content .= "ID da venda mais cara: " . $biggestSale['ID_Venda'] . "\n";
  $content .= "Pior vendedor de todos: " . findWorstSeller() . "\n";
  
  file_put_contents($output_file, $content);
}

function checkDir($dir) {
  $files = scandir($dir);

  array_shift($files);
  array_shift($files);

  foreach ($files as $file) {
    $extension = pathinfo($file, PATHINFO_EXTENSION);
    if ($extension !== 'dat') {
      return false;
    }
  }

  return true;
}

function isDirEmpty($dir) {
  $files = scandir($dir);
  return count($files) === 2;
}

function main () {
  $out = "data/out/";
  global $input_dir;

  if(isDirEmpty($input_dir)) {
    echo "Empty directory";
  } else {
    if(!is_dir($out)) {
      mkdir($out, 0755);
    }
  
    if (checkDir($input_dir)) {
      getData();
      outputData();
      echo "Script executed successfully";
    } else {
      echo "The directory $input_dir only accepts files with the .dat extension";
    }
  }

}

main();
