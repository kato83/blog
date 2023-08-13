// バンドルしたくないものは require 指定
const aws = require('aws-sdk');

// バンドルしたいものは ESModule 形式で指定
// ※import type は型なのでバンドルされない
import type AWS from 'aws-sdk';
import { factory, detectPrng } from 'ulid';

const prng = detectPrng(true);
const ulid = factory(prng);

const dynamoDB: AWS.DynamoDB.DocumentClient = new aws.DynamoDB.DocumentClient();

exports.handler = async (_event) => {
  console.log(ulid());

  const params = {
    TableName: 'blog',  // DynamoDB テーブルの名前を指定
    Item: {
      id: Math.random().toString(32).substring(2),
      created_at: new Date().toString(),
      content: 'this is sample'
    }
  };

  try {
    const result = await dynamoDB.put(params).promise();
    console.log('Item added successfully:', result);
    return {
      statusCode: 200,
      body: JSON.stringify('Item added successfully'),
    };
  } catch (error) {
    console.error('Error adding item:', error);
    return {
      statusCode: 500,
      body: JSON.stringify('Error adding item'),
    };
  }
}