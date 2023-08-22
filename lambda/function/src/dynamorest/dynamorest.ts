// バンドルしたくないものは require 指定
const aws = require('aws-sdk');

// バンドルしたいものは ESModule 形式で指定
// ※import type は型なのでバンドルされない
import type AWS from 'aws-sdk';
import { factory, detectPrng } from 'ulid';

const prng = detectPrng(true);
const ulid = factory(prng);

const dynamoDB: AWS.DynamoDB.DocumentClient = new aws.DynamoDB.DocumentClient();

type ApiGatewayEvent = {
  httpMethod: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE'
  body: string;
}

const createResponse = (statusCode: number, message: string) => ({
  statusCode,
  headers: {
    'Access-Control-Allow-Origin': '*'
  },
  body: JSON.stringify({ message }),
  isBase64Encoded: false
});

exports.handler = async (event: ApiGatewayEvent) => {
  const id = ulid();

  const payload = await new Promise(resolve => resolve(JSON.parse(event.body)))
    .catch(_ => null);

  if (payload === null) return createResponse(400, 'Request body is invalid.');

  const param = {
    TableName: 'blog',
    Item: {}
  };

  try {
    switch (event.httpMethod) {
      case 'GET':
        break;
      case 'POST': {
        const date = new Date().toISOString();
        const result = await dynamoDB.put({
          ...param,
          Item: {
            ...payload,
            id: id,
            created_at: date,
            updated_at: date
          }
        }).promise();
        console.debug(result);

        return createResponse(200, 'OK');
      }
      case 'DELETE': {
        const result = await dynamoDB.delete({
          TableName: 'blog',
          Key: payload as any
        }).promise();
        console.debug(result);

        return createResponse(200, 'OK');
      }
      default:
        return createResponse(403, 'Request method is invalid.');
    }
  } catch (error) {
    console.error('Error adding item:', error);
    return createResponse(500, 'ERROR');
  }

  return createResponse(403, 'Request method is invalid.');
}