// バンドルしたくないものは require 指定
const markdown = require('markdown-wasm');


exports.handler = async (event: any, context: any, _callback: any) => {
  console.debug(event);
  console.debug(context);

  const html = await new Promise(resolve => resolve(markdown.parse(JSON.parse(event.body)?.markdown)))
    .catch(_ => null);

  if (html === null) {
    return {
      statusCode: 400,
      headers: {
        'Access-Control-Allow-Origin': '*'
      },
      body: JSON.stringify({ message: 'Request body is invalid.' }),
      isBase64Encoded: false
    };
  } else {
    return {
      statusCode: 200,
      headers: {
        'Access-Control-Allow-Origin': '*'
      },
      body: JSON.stringify({ html }),
      isBase64Encoded: false
    };
  }
};