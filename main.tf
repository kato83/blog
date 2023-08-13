terraform {
  backend "s3" {}
}

provider "aws" {
  region  = "ap-northeast-1"
  profile = "terraform"
}

# Lambda の JS の zip 圧縮
data "archive_file" "zip_js" {
  type        = "zip"
  source_dir  = "dist/js/md2html"
  output_path = "dist/md2html.zip"
}

data "archive_file" "zip_dynamorest" {
  type        = "zip"
  source_dir  = "dist/js/dynamorest"
  output_path = "dist/dynamorest.zip"
}

# markdown-wasm のレイヤー用の zip 圧縮
data "archive_file" "zip_markdown" {
  type        = "zip"
  source_dir  = "markdown"
  output_path = "dist/mawkdown-wasm.zip"
}

# markdown-wasm のレイヤー用の zip 圧縮
data "archive_file" "zip_aws_sdk" {
  type        = "zip"
  source_dir  = "aws-sdk"
  output_path = "dist/aws-sdk.zip"
}

resource "aws_api_gateway_rest_api" "blog_api" {
  name = "BlogAPI"
  body = templatefile("./openapi.yml", {
    md2html_arn = aws_lambda_function.md2html.invoke_arn,
    dynamorest_arn = aws_lambda_function.dynamorest.invoke_arn
  })
}

resource "aws_api_gateway_deployment" "deployment" {
  rest_api_id = aws_api_gateway_rest_api.blog_api.id
  depends_on  = [aws_api_gateway_rest_api.blog_api]
  stage_name  = "prod"
  triggers = {
    # resource "aws_lambda_function" "api" の内容が変わるごとにデプロイされるようにする
    redeployment = sha1(jsonencode(aws_api_gateway_rest_api.blog_api))
  }
}

# Lambda関数を作成
resource "aws_lambda_function" "md2html" {
  function_name    = "md2html"
  role             = aws_iam_role.this.arn
  runtime          = "nodejs18.x"
  handler          = "md2html.handler"
  source_code_hash = data.archive_file.zip_js.output_base64sha256
  filename         = data.archive_file.zip_js.output_path
  layers = [
    aws_lambda_layer_version.markdown.arn,
    aws_lambda_layer_version.aws_sdk.arn,
  ]
}

resource "aws_lambda_permission" "lambda_permit" {
  action        = "lambda:InvokeFunction"
  function_name = aws_lambda_function.md2html.arn
  principal     = "apigateway.amazonaws.com"
  source_arn    = "${aws_api_gateway_rest_api.blog_api.execution_arn}/*"
}

# Lambda関数を作成
resource "aws_lambda_function" "dynamorest" {
  function_name    = "dynamorest"
  role             = aws_iam_role.this.arn
  runtime          = "nodejs18.x"
  handler          = "dynamorest.handler"
  source_code_hash = data.archive_file.zip_dynamorest.output_base64sha256
  filename         = data.archive_file.zip_dynamorest.output_path
  layers = [
    aws_lambda_layer_version.markdown.arn,
    aws_lambda_layer_version.aws_sdk.arn,
  ]
}

resource "aws_lambda_permission" "lambda_permit2" {
  action        = "lambda:InvokeFunction"
  function_name = aws_lambda_function.dynamorest.arn
  principal     = "apigateway.amazonaws.com"
  source_arn    = "${aws_api_gateway_rest_api.blog_api.execution_arn}/*"
}


# lambda の レイヤー作成（markdown-wasm）
resource "aws_lambda_layer_version" "markdown" {
  layer_name          = "markdown-wasm"
  filename            = data.archive_file.zip_markdown.output_path
  compatible_runtimes = ["nodejs18.x"]
  source_code_hash    = data.archive_file.zip_markdown.output_base64sha256
}

# lambda の レイヤー作成（aws-sdk）
resource "aws_lambda_layer_version" "aws_sdk" {
  layer_name          = "aws-sdk"
  filename            = data.archive_file.zip_aws_sdk.output_path
  compatible_runtimes = ["nodejs18.x"]
  source_code_hash    = data.archive_file.zip_aws_sdk.output_base64sha256
}

# Lambda関数に対するIAMロールを作成
resource "aws_iam_role" "this" {
  name = "lambda-blog-role"

  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Action = "sts:AssumeRole"
        Effect = "Allow"
        Principal = {
          Service = [
            "lambda.amazonaws.com",
            "apigateway.amazonaws.com"
          ]
        }
      }
    ]
  })
}

# Lambda関数にアクセスを許可するIAMポリシーをアタッチ
resource "aws_iam_role_policy_attachment" "lambda_basic_execution_access" {
  policy_arn = "arn:aws:iam::aws:policy/service-role/AWSLambdaBasicExecutionRole"
  role       = aws_iam_role.this.name
}

# function url 作成
resource "aws_lambda_function_url" "aws_lambda_function_url" {
  function_name      = aws_lambda_function.md2html.function_name
  authorization_type = "NONE"
}

resource "aws_iam_role_policy" "policy" {
  name = "access-policy"
  role = aws_iam_role.this.id
  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Effect = "Allow"
        Action = [
          "dynamodb:PutItem",
          "dynamodb:DeleteItem",
        ]
        Resource = "*"
      }
    ]
  })
}

resource "aws_dynamodb_table" "blog" {
  name         = "blog"
  billing_mode = "PAY_PER_REQUEST"
  hash_key     = "id"
  range_key    = "created_at"

  attribute {
    name = "id"
    type = "S"
  }

  attribute {
    name = "created_at"
    type = "S"
  }
}