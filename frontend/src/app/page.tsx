'use client';
import styles from "./page.module.css";
import { Input, List, Space } from "antd";
import { useState } from "react";
import React from "react";
import { PhoneOutlined, GlobalOutlined, TagOutlined } from '@ant-design/icons';

interface resultItem {
  title: string;
  phone: string;
  website?: string;
  address?: string;
  thumbnailUrl: string;
  type: string;
}


const transformData = (results: any[]) => {
  const tranformedResults: resultItem[] = [];

  results.forEach(element => {
    tranformedResults.push({
      title: element.title,
      phone: element.phone,
      website: element.website,
      address: element.address,
      thumbnailUrl: element.thumbnail,
      type: element.type,
    });
  });

  return tranformedResults;
}

async function getResults(searchQuery: string) {
  const params = new URLSearchParams();
  const baseUrl = new URL("http://localhost:8000/");

  params.append("query", searchQuery)
  baseUrl.search = params.toString();

  const res = await fetch(baseUrl, {
    cache: 'force-cache',
    method: 'GET',
    headers: {
      "Content-Type": "application/json",
    },
  }).then((res) => res.json());

  return transformData(res);
}

const IconText = ({ icon, text }: { icon: React.FC; text: string }) => (
  <Space>
    {React.createElement(icon)}
    {text}
  </Space>
);

export default function Home() {
  const [results, setResults] = useState<resultItem[]>([]);

  const onSearch = async (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const results = await getResults(e.target.value);
    setResults(results);
  };

  return (
    <div className={'main-container'}>
      <main>
        <div style={{ 'width': '50%' }}>
          <Input placeholder="ex: randonnée, escalade..." onChange={onSearch} addonBefore='Que faire aux alentours ?' />
        </div>
        <List
          itemLayout="vertical"
          size="large"
          pagination={{ position: 'both', align: 'center' }}
          dataSource={results}
          renderItem={(item) => (
            <List.Item
             key={item.phone}
             actions={[
              <IconText icon={PhoneOutlined} text={item.phone ? item.phone : "No phone"} key="list-vertical-star-o" />,
              <IconText icon={GlobalOutlined} text={item.website ? item.website : "No website"} key="list-vertical-like-o" />,
              <IconText icon={TagOutlined} text={item.type} key="list-vertical-message" />,
            ]}
             extra={
              <img
                width={272}
                alt="logo"
                src={item.thumbnailUrl}
              />
            }> 
              <List.Item.Meta
                title={item.title}
                description={item.address}
              />
            </List.Item>
          )}
        />
      </main>
      <footer className={styles.footer}>
        Made with ❤️ by Cyrille NICAISE for GC TECH
      </footer>
    </div>
  );
}
